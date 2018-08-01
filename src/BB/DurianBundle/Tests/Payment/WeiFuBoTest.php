<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\WeiFuBo;

class WeiFuBoTest extends DurianTestCase
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

        $weiFuBo = new WeiFuBo();
        $weiFuBo->getVerifyData();
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

        $weiFuBo = new WeiFuBo();
        $weiFuBo->setPrivateKey('1234');
        $weiFuBo->setOptions($sourceData);
        $weiFuBo->getVerifyData();
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
            'number' => '10533',
            'paymentVendorId' => '999',
            'amount' => '0.01',
            'orderId' => '201802070000004154',
            'notify_url' => 'http://pay.my/pay/return.php',
            'orderCreateDate' => '2018-02-07 14:34:34',
        ];

        $weiFuBo = new WeiFuBo();
        $weiFuBo->setPrivateKey('1234');
        $weiFuBo->setOptions($sourceData);
        $weiFuBo->getVerifyData();
    }

    /**
     * 測試支付加密
     */
    public function testPayEncode()
    {
        $sourceData = [
            'number' => '10533',
            'paymentVendorId' => '1098',
            'amount' => '0.01',
            'orderId' => '201802070000004154',
            'notify_url' => 'http://pay.my/pay/return.php',
            'orderCreateDate' => '2018-02-07 14:34:34',
        ];

        $weiFuBo = new WeiFuBo();
        $weiFuBo->setPrivateKey('1234');
        $weiFuBo->setOptions($sourceData);
        $encodeData = $weiFuBo->getVerifyData();

        $this->assertEquals('GET', $weiFuBo->getPayMethod());
        $this->assertEquals('10533', $encodeData['pay_memberid']);
        $this->assertEquals('201802070000004154', $encodeData['pay_orderid']);
        $this->assertEquals('2018-02-07 14:34:34', $encodeData['pay_applydate']);
        $this->assertEquals('946', $encodeData['pay_bankcode']);
        $this->assertEquals('0.01', $encodeData['pay_amount']);
        $this->assertEquals('http://pay.my/pay/return.php', $encodeData['pay_notifyurl']);
        $this->assertEquals('http://pay.my/pay/return.php', $encodeData['pay_callbackurl']);
        $this->assertEquals('56E7FB02C056C614698EA421E7B90027', $encodeData['pay_md5sign']);
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

        $weiFuBo = new WeiFuBo();
        $weiFuBo->verifyOrderPayment([]);
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

        $weiFuBo = new WeiFuBo();
        $weiFuBo->setPrivateKey('1234');
        $weiFuBo->verifyOrderPayment([]);
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
            'memberid' => '10533',
            'orderid' => '201802070000004154',
            'transaction_id' => '201802070000004154',
            'amount' => '0.01',
            'datetime' => '2018-02-07 14:34:34',
            'returncode' => '00',
            'attach' => '',
        ];

        $weiFuBo = new WeiFuBo();
        $weiFuBo->setPrivateKey('1234');
        $weiFuBo->setOptions($sourceData);
        $weiFuBo->verifyOrderPayment([]);
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
            'memberid' => '10533',
            'orderid' => '201802070000004154',
            'transaction_id' => '201802070000004154',
            'amount' => '0.01',
            'datetime' => '2018-02-07 14:34:34',
            'returncode' => '00',
            'sign' => '778DAF3E1C5C3A8F7CF73E8BDAC20B1C',
            'attach' => '',
        ];

        $weiFuBo = new WeiFuBo();
        $weiFuBo->setPrivateKey('1234');
        $weiFuBo->setOptions($sourceData);
        $weiFuBo->verifyOrderPayment([]);
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
            'memberid' => '10533',
            'orderid' => '201802070000004154',
            'transaction_id' => '201802070000004154',
            'amount' => '0.01',
            'datetime' => '2018-02-07 14:34:34',
            'returncode' => '01',
            'sign' => '5F3B682BF48E76CA3E3D30D198E0B5D9',
            'attach' => '',
        ];

        $weiFuBo = new WeiFuBo();
        $weiFuBo->setPrivateKey('1234');
        $weiFuBo->setOptions($sourceData);
        $weiFuBo->verifyOrderPayment([]);
    }

    /**
     * 測試返回結果為訂單編號錯誤
     */
    public function testOrderIdError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $sourceData = [
            'memberid' => '10533',
            'orderid' => '201802070000004154',
            'transaction_id' => '201802070000004154',
            'amount' => '0.01',
            'datetime' => '2018-02-07 14:34:34',
            'returncode' => '00',
            'sign' => '41E246824948AAF04F3BF1FC1895E6BA',
            'attach' => '',
        ];

        $entry = [
            'id' => '201802070000004153',
            'amount' => '0.01',
        ];

        $weiFuBo = new WeiFuBo();
        $weiFuBo->setPrivateKey('1234');
        $weiFuBo->setOptions($sourceData);
        $weiFuBo->verifyOrderPayment($entry);
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
            'memberid' => '10533',
            'orderid' => '201802070000004154',
            'transaction_id' => '201802070000004154',
            'amount' => '0.01',
            'datetime' => '2018-02-07 14:34:34',
            'returncode' => '00',
            'sign' => '41E246824948AAF04F3BF1FC1895E6BA',
            'attach' => '',
        ];

        $entry = [
            'id' => '201802070000004154',
            'amount' => '1',
        ];

        $weiFuBo = new WeiFuBo();
        $weiFuBo->setPrivateKey('1234');
        $weiFuBo->setOptions($sourceData);
        $weiFuBo->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testPaySuccess()
    {
        $sourceData = [
            'memberid' => '10533',
            'orderid' => '201802070000004154',
            'transaction_id' => '201802070000004154',
            'amount' => '0.01',
            'datetime' => '2018-02-07 14:34:34',
            'returncode' => '00',
            'sign' => '41E246824948AAF04F3BF1FC1895E6BA',
            'attach' => '',
        ];

        $entry = [
            'id' => '201802070000004154',
            'amount' => '0.01',
        ];

        $weiFuBo = new WeiFuBo();
        $weiFuBo->setPrivateKey('1234');
        $weiFuBo->setOptions($sourceData);
        $weiFuBo->verifyOrderPayment($entry);

        $this->assertEquals('OK', $weiFuBo->getMsg());
    }
}
