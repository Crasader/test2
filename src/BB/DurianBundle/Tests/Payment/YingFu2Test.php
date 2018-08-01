<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\YingFu2;

class YingFu2Test extends DurianTestCase
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

        $yingFu2 = new YingFu2();
        $yingFu2->getVerifyData();
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

        $yingFu2 = new YingFu2();
        $yingFu2->setPrivateKey('test');
        $yingFu2->getVerifyData();
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
            'number' => '10008',
            'notify_url' => 'http://fufutest.000webhostapp.com/pay/',
            'paymentVendorId' => '999',
            'orderId' => '201803290000011609',
            'amount' => '0.10',
            'orderCreateDate' => '2018-03-29 10:06:06',
        ];

        $yingFu2 = new YingFu2();
        $yingFu2->setPrivateKey('test');
        $yingFu2->setOptions($options);
        $yingFu2->getVerifyData();
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $options = [
            'notify_url' => 'http://fufutest.000webhostapp.com/pay/',
            'paymentVendorId' => '1098',
            'orderId' => '201803290000011609',
            'amount' => '1.00',
            'number' => '10008',
            'orderCreateDate' => '2018-03-29 10:06:06',
        ];

        $yingFu2 = new YingFu2();
        $yingFu2->setPrivateKey('test');
        $yingFu2->setOptions($options);
        $encodeData = $yingFu2->getVerifyData();

        $this->assertEquals('10008', $encodeData['pay_memberid']);
        $this->assertEquals('20180329000001160910008', $encodeData['pay_orderid']);
        $this->assertEquals('1.00', $encodeData['pay_amount']);
        $this->assertEquals('2018-03-29 10:06:06', $encodeData['pay_applydate']);
        $this->assertEquals('904', $encodeData['pay_bankcode']);
        $this->assertEquals('http://fufutest.000webhostapp.com/pay/', $encodeData['pay_notifyurl']);
        $this->assertEquals('33CF6B94430955A84F07926233EE1CAA', $encodeData['pay_md5sign']);
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

        $yingFu2 = new YingFu2();
        $yingFu2->verifyOrderPayment([]);
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

        $yingFu2 = new YingFu2();
        $yingFu2->setPrivateKey('test');
        $yingFu2->verifyOrderPayment([]);
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
            'memberid' => '10008',
            'orderid' => '20180329000001160910008',
            'transaction_id' => '20180330174700524857',
            'amount' => '0.01',
            'datetime' => '2018-03-29 10:06:06',
            'returncode' => '00',
        ];

        $yingFu2 = new YingFu2();
        $yingFu2->setPrivateKey('test');
        $yingFu2->setOptions($options);
        $yingFu2->verifyOrderPayment([]);
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
            'memberid' => '10008',
            'orderid' => '20180329000001160910008',
            'transaction_id' => '20180330174700524857',
            'amount' => '0.01',
            'datetime' => '2018-03-29 10:06:06',
            'returncode' => '00',
            'sign' => '123456798798',
        ];

        $yingFu2 = new YingFu2();
        $yingFu2->setPrivateKey('test');
        $yingFu2->setOptions($options);
        $yingFu2->verifyOrderPayment([]);
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
            'memberid' => '10008',
            'orderid' => '20180329000001160910008',
            'transaction_id' => '20180330174700524857',
            'amount' => '0.01',
            'datetime' => '2018-03-29 10:06:06',
            'returncode' => '99999',
            'sign' => 'D6FFC80A0C48138A967635F9142DA1B9',
        ];

        $yingFu2 = new YingFu2();
        $yingFu2->setPrivateKey('test');
        $yingFu2->setOptions($options);
        $yingFu2->verifyOrderPayment([]);
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
            'memberid' => '10008',
            'orderid' => '20180329000001160910008',
            'transaction_id' => '20180330174700524857',
            'amount' => '0.01',
            'datetime' => '2018-03-29 10:06:06',
            'returncode' => '00',
            'sign' => 'C546481DF46800FB4189DC2C9FAC1A49',
        ];

        $entry = ['id' => '201706050000001234'];

        $yingFu2 = new YingFu2();
        $yingFu2->setPrivateKey('test');
        $yingFu2->setOptions($options);
        $yingFu2->verifyOrderPayment($entry);
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
            'memberid' => '10008',
            'orderid' => '20180329000001160910008',
            'transaction_id' => '20180330174700524857',
            'amount' => '0.01',
            'datetime' => '2018-03-29 10:06:06',
            'returncode' => '00',
            'sign' => 'C546481DF46800FB4189DC2C9FAC1A49',
        ];

        $entry = [
            'id' => '201803290000011609',
            'amount' => '15.00',
        ];

        $yingFu2 = new YingFu2();
        $yingFu2->setPrivateKey('test');
        $yingFu2->setOptions($options);
        $yingFu2->verifyOrderPayment($entry);
    }

    /**
     * 測試返回驗證成功
     */
    public function testReturnSuccess()
    {
        $options = [
            'memberid' => '10008',
            'orderid' => '20180329000001160910008',
            'transaction_id' => '20180330174700524857',
            'amount' => '0.01',
            'datetime' => '2018-03-29 10:06:06',
            'returncode' => '00',
            'sign' => 'C546481DF46800FB4189DC2C9FAC1A49',
        ];

        $entry = [
            'id' => '201803290000011609',
            'amount' => '0.01',
        ];

        $yingFu2 = new YingFu2();
        $yingFu2->setPrivateKey('test');
        $yingFu2->setOptions($options);
        $yingFu2->verifyOrderPayment($entry);

        $this->assertEquals('ok', $yingFu2->getMsg());
    }
}
