<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\DianYun;

class DianYunTest extends DurianTestCase
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

        $dianYun = new DianYun();
        $dianYun->getVerifyData();
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

        $dianYun = new DianYun();
        $dianYun->setPrivateKey('test');
        $dianYun->getVerifyData();
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
            'number' => '10309',
            'notify_url' => 'http://two123.comxa.com/',
            'paymentVendorId' => '999',
            'orderId' => '201706050000006534',
            'amount' => '0.10',
            'orderCreateDate' => '2017-06-06 10:06:06',
        ];

        $dianYun = new DianYun();
        $dianYun->setPrivateKey('test');
        $dianYun->setOptions($options);
        $dianYun->getVerifyData();
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $options = [
            'notify_url' => 'http://two123.comxa.com/',
            'paymentVendorId' => '1092',
            'orderId' => '201801240000008862',
            'amount' => '1.00',
            'number' => '10309',
            'orderCreateDate' => '2018-01-24 09:13:40',
        ];

        $dianYun = new DianYun();
        $dianYun->setPrivateKey('test');
        $dianYun->setOptions($options);
        $encodeData = $dianYun->getVerifyData();

        $this->assertEquals($options['number'], $encodeData['pay_memberid']);
        $this->assertEquals('', $encodeData['pay_orderid']);
        $this->assertEquals($options['amount'], $encodeData['pay_amount']);
        $this->assertEquals($options['orderCreateDate'], $encodeData['pay_applydate']);
        $this->assertEquals('ALIPAY', $encodeData['pay_bankcode']);
        $this->assertEquals($options['notify_url'], $encodeData['pay_notifyurl']);
        $this->assertEquals($options['notify_url'], $encodeData['pay_callbackurl']);
        $this->assertEquals('DFYzfb', $encodeData['tongdao']);
        $this->assertEquals($options['orderId'], $encodeData['pay_reserved1']);
        $this->assertEquals('0771D26592A95EE51076CDC231EDB2AD', $encodeData['pay_md5sign']);
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

        $dianYun = new DianYun();
        $dianYun->verifyOrderPayment([]);
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

        $dianYun = new DianYun();
        $dianYun->setPrivateKey('test');
        $dianYun->verifyOrderPayment([]);
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
            'memberid' => '10309',
            'orderid' => '103092018012409131828386',
            'amount' => '0.100',
            'datetime' => '20180124091340',
            'returncode' => '00',
            'reserved1' => '201801240000008862',
        ];

        $dianYun = new DianYun();
        $dianYun->setPrivateKey('test');
        $dianYun->setOptions($options);
        $dianYun->verifyOrderPayment([]);
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
            'memberid' => '10309',
            'orderid' => '103092018012409131828386',
            'amount' => '0.100',
            'datetime' => '20180124091340',
            'returncode' => '00',
            'reserved1' => '201801240000008862',
            'sign' => '12345679',
        ];

        $dianYun = new DianYun();
        $dianYun->setPrivateKey('test');
        $dianYun->setOptions($options);
        $dianYun->verifyOrderPayment([]);
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
            'memberid' => '10309',
            'orderid' => '103092018012409131828386',
            'amount' => '0.100',
            'datetime' => '20180124091340',
            'returncode' => '99',
            'reserved1' => '201801240000008862',
            'sign' => 'D765A686D99E6029CA8D1BDD95B00BC4',
        ];

        $dianYun = new DianYun();
        $dianYun->setPrivateKey('test');
        $dianYun->setOptions($options);
        $dianYun->verifyOrderPayment([]);
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
            'memberid' => '10309',
            'orderid' => '103092018012409131828386',
            'amount' => '0.100',
            'datetime' => '20180124091340',
            'returncode' => '00',
            'reserved1' => '201801240000008862',
            'sign' => '9BF5D0573123F73B5193FDAB0CA6422C',
        ];

        $entry = ['id' => '201706050000001234'];

        $dianYun = new DianYun();
        $dianYun->setPrivateKey('test');
        $dianYun->setOptions($options);
        $dianYun->verifyOrderPayment($entry);
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
            'memberid' => '10309',
            'orderid' => '103092018012409131828386',
            'amount' => '0.100',
            'datetime' => '20180124091340',
            'returncode' => '00',
            'reserved1' => '201801240000008862',
            'sign' => '9BF5D0573123F73B5193FDAB0CA6422C',
        ];

        $entry = [
            'id' => '201801240000008862',
            'amount' => '15.00',
        ];

        $dianYun = new DianYun();
        $dianYun->setPrivateKey('test');
        $dianYun->setOptions($options);
        $dianYun->verifyOrderPayment($entry);
    }

    /**
     * 測試返回驗證成功
     */
    public function testReturnSuccess()
    {
        $options = [
            'memberid' => '10309',
            'orderid' => '103092018012409131828386',
            'amount' => '0.100',
            'datetime' => '20180124091340',
            'returncode' => '00',
            'reserved1' => '201801240000008862',
            'sign' => '9BF5D0573123F73B5193FDAB0CA6422C',
        ];

        $entry = [
            'id' => '201801240000008862',
            'amount' => '0.1',
        ];

        $dianYun = new DianYun();
        $dianYun->setPrivateKey('test');
        $dianYun->setOptions($options);
        $dianYun->verifyOrderPayment($entry);

        $this->assertEquals('OK', $dianYun->getMsg());
    }
}
