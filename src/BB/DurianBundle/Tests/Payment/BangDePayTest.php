<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\BangDePay;

class BangDePayTest extends DurianTestCase
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

        $eboopay = new BangDePay();
        $eboopay->getVerifyData();
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

        $eboopay = new BangDePay();
        $eboopay->setPrivateKey('test');
        $eboopay->getVerifyData();
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

        $eboopay = new BangDePay();
        $eboopay->setPrivateKey('test');
        $eboopay->setOptions($options);
        $eboopay->getVerifyData();
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $options = [
            'notify_url' => 'http://two123.comxa.com/',
            'paymentVendorId' => '1092',
            'orderId' => '201608160000003698',
            'amount' => '1.00',
            'number' => '33728',
            'orderCreateDate' => '2017-06-06 10:06:06',
        ];

        $eboopay = new BangDePay();
        $eboopay->setPrivateKey('test');
        $eboopay->setOptions($options);
        $encodeData = $eboopay->getVerifyData();

        $this->assertEquals('33728', $encodeData['pay_memberid']);
        $this->assertEquals('20160816000000369833728', $encodeData['pay_orderid']);
        $this->assertEquals('1.00', $encodeData['pay_amount']);
        $this->assertEquals('2017-06-06 10:06:06', $encodeData['pay_applydate']);
        $this->assertEquals('ALIPAY', $encodeData['pay_bankcode']);
        $this->assertEquals('http://two123.comxa.com/', $encodeData['pay_notifyurl']);
        $this->assertEquals('E1E854119E7DB884AF015539E01F180E', $encodeData['pay_md5sign']);
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

        $eboopay = new BangDePay();
        $eboopay->verifyOrderPayment([]);
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

        $eboopay = new BangDePay();
        $eboopay->setPrivateKey('test');
        $eboopay->verifyOrderPayment([]);
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
            'orderid' => '20170605000000653433728',
            'amount' => '0.010',
            'datetime' => '2017-06-05 17:17:14',
            'returncode' => '00000',
        ];

        $eboopay = new BangDePay();
        $eboopay->setPrivateKey('test');
        $eboopay->setOptions($options);
        $eboopay->verifyOrderPayment([]);
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
            'orderid' => '20170605000000653433728',
            'amount' => '0.010',
            'datetime' => '2017-06-05 17:17:14',
            'returncode' => '00000',
            'sign' => '123456798798',
        ];

        $eboopay = new BangDePay();
        $eboopay->setPrivateKey('test');
        $eboopay->setOptions($options);
        $eboopay->verifyOrderPayment([]);
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
            'orderid' => '20170605000000653433728',
            'amount' => '0.010',
            'datetime' => '2017-06-05 17:17:14',
            'returncode' => '99999',
            'sign' => '156D0AEA07B7D9D41F01B07036D1BBD3',
        ];

        $eboopay = new BangDePay();
        $eboopay->setPrivateKey('test');
        $eboopay->setOptions($options);
        $eboopay->verifyOrderPayment([]);
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
            'orderid' => '20170605000000653433728',
            'amount' => '0.010',
            'datetime' => '2017-06-05 17:17:14',
            'returncode' => '00000',
            'sign' => 'B288F4E7370B9DC25D1784C2663FC6EE',
        ];

        $entry = ['id' => '201706050000001234'];

        $eboopay = new BangDePay();
        $eboopay->setPrivateKey('test');
        $eboopay->setOptions($options);
        $eboopay->verifyOrderPayment($entry);
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
            'orderid' => '20170605000000653433728',
            'amount' => '0.010',
            'datetime' => '2017-06-05 17:17:14',
            'returncode' => '00000',
            'sign' => 'B288F4E7370B9DC25D1784C2663FC6EE',
        ];

        $entry = [
            'id' => '201706050000006534',
            'amount' => '15.00',
        ];

        $eboopay = new BangDePay();
        $eboopay->setPrivateKey('test');
        $eboopay->setOptions($options);
        $eboopay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回驗證成功
     */
    public function testReturnSuccess()
    {
        $options = [
            'memberid' => '33728',
            'orderid' => '20170605000000653433728',
            'amount' => '0.010',
            'datetime' => '2017-06-05 17:17:14',
            'returncode' => '00000',
            'sign' => 'B288F4E7370B9DC25D1784C2663FC6EE',
        ];

        $entry = [
            'id' => '201706050000006534',
            'amount' => '0.01',
        ];

        $eboopay = new BangDePay();
        $eboopay->setPrivateKey('test');
        $eboopay->setOptions($options);
        $eboopay->verifyOrderPayment($entry);

        $this->assertEquals('success', $eboopay->getMsg());
    }
}
