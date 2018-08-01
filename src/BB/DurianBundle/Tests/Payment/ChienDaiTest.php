<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\ChienDai;

class ChienDaiTest extends DurianTestCase
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

        $chienDai = new ChienDai();
        $chienDai->getVerifyData();
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

        $chienDai = new ChienDai();
        $chienDai->setPrivateKey('test');
        $chienDai->getVerifyData();
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
            'number' => '10044',
            'notify_url' => 'http://two123.comxa.com/',
            'paymentVendorId' => '999',
            'orderId' => '201711200000007706',
            'amount' => '0.10',
            'orderCreateDate' => '2017-11-20 10:06:06',
            'username' => 'two',
        ];

        $chienDai = new ChienDai();
        $chienDai->setPrivateKey('test');
        $chienDai->setOptions($options);
        $chienDai->getVerifyData();
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $options = [
            'notify_url' => 'http://two123.comxa.com/',
            'paymentVendorId' => '1090',
            'orderId' => '201608160000003698',
            'amount' => '1.00',
            'number' => '10044',
            'orderCreateDate' => '2017-11-20 10:06:06',
            'username' => 'two',
        ];

        $chienDai = new ChienDai();
        $chienDai->setPrivateKey('test');
        $chienDai->setOptions($options);
        $encodeData = $chienDai->getVerifyData();

        $this->assertEquals($options['number'], $encodeData['pay_memberid']);
        $this->assertEquals($options['orderId'], $encodeData['pay_orderid']);
        $this->assertEquals($options['orderCreateDate'], $encodeData['pay_applydate']);
        $this->assertEquals('902', $encodeData['pay_bankcode']);
        $this->assertEquals($options['notify_url'], $encodeData['pay_notifyurl']);
        $this->assertEquals($options['notify_url'], $encodeData['pay_callbackurl']);
        $this->assertEquals($options['amount'], $encodeData['pay_amount']);
        $this->assertEquals('', $encodeData['pay_attach']);
        $this->assertEquals('two', $encodeData['pay_productname']);
        $this->assertEquals('', $encodeData['pay_productnum']);
        $this->assertEquals('', $encodeData['pay_productdesc']);
        $this->assertEquals('', $encodeData['pay_producturl']);
        $this->assertEquals('5EC46EC0F6B01F3D29F500A8D5B043AA', $encodeData['pay_md5sign']);
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

        $chienDai = new ChienDai();
        $chienDai->verifyOrderPayment([]);
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

        $chienDai = new ChienDai();
        $chienDai->setPrivateKey('test');
        $chienDai->verifyOrderPayment([]);
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
            'memberid' => '10044',
            'orderid' => '201711210000007717',
            'transaction_id' => '20171121151455102551',
            'amount' => '1.00',
            'datetime' => '20171121151631',
            'returncode' => '00',
            'attach' => '',
        ];

        $chienDai = new ChienDai();
        $chienDai->setPrivateKey('test');
        $chienDai->setOptions($options);
        $chienDai->verifyOrderPayment([]);
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
            'memberid' => '10044',
            'orderid' => '201711210000007717',
            'transaction_id' => '20171121151455102551',
            'amount' => '1.00',
            'datetime' => '20171121151631',
            'returncode' => '00',
            'sign' => 'C1E41274D34C55894348D33CDBD1FFBE',
            'attach' => '',
        ];

        $chienDai = new ChienDai();
        $chienDai->setPrivateKey('test');
        $chienDai->setOptions($options);
        $chienDai->verifyOrderPayment([]);
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
            'memberid' => '10044',
            'orderid' => '201711210000007717',
            'transaction_id' => '20171121151455102551',
            'amount' => '1.00',
            'datetime' => '20171121151631',
            'returncode' => '99',
            'sign' => '4D84101ABAF38E6DC9A65C8C07AD21E0',
            'attach' => '',
        ];

        $chienDai = new ChienDai();
        $chienDai->setPrivateKey('test');
        $chienDai->setOptions($options);
        $chienDai->verifyOrderPayment([]);
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
            'memberid' => '10044',
            'orderid' => '201711210000007717',
            'transaction_id' => '20171121151455102551',
            'amount' => '1.00',
            'datetime' => '20171121151631',
            'returncode' => '00',
            'sign' => 'F450D3D1D0BB563F4818D80AF2814E4A',
            'attach' => '',
        ];

        $entry = ['id' => '201706050000001234'];

        $chienDai = new ChienDai();
        $chienDai->setPrivateKey('test');
        $chienDai->setOptions($options);
        $chienDai->verifyOrderPayment($entry);
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
            'memberid' => '10044',
            'orderid' => '201711210000007717',
            'transaction_id' => '20171121151455102551',
            'amount' => '1.00',
            'datetime' => '20171121151631',
            'returncode' => '00',
            'sign' => 'F450D3D1D0BB563F4818D80AF2814E4A',
            'attach' => '',
        ];

        $entry = [
            'id' => '201711210000007717',
            'amount' => '15.00',
        ];

        $chienDai = new ChienDai();
        $chienDai->setPrivateKey('test');
        $chienDai->setOptions($options);
        $chienDai->verifyOrderPayment($entry);
    }

    /**
     * 測試返回驗證成功
     */
    public function testReturnSuccess()
    {
        $options = [
            'memberid' => '10044',
            'orderid' => '201711210000007717',
            'transaction_id' => '20171121151455102551',
            'amount' => '1.00',
            'datetime' => '20171121151631',
            'returncode' => '00',
            'sign' => 'F450D3D1D0BB563F4818D80AF2814E4A',
            'attach' => '',
        ];

        $entry = [
            'id' => '201711210000007717',
            'amount' => '1.00',
        ];

        $chienDai = new ChienDai();
        $chienDai->setPrivateKey('test');
        $chienDai->setOptions($options);
        $chienDai->verifyOrderPayment($entry);

        $this->assertEquals('OK', $chienDai->getMsg());
    }
}
