<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\XunBill;

class XunBillTest extends DurianTestCase
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

        $xunBill = new XunBill();
        $xunBill->getVerifyData();
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

        $xunBill = new XunBill();
        $xunBill->setPrivateKey('test');
        $xunBill->getVerifyData();
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
            'number' => '10020',
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '999',
            'orderId' => '201709070000004556',
            'amount' => '0.10',
            'orderCreateDate' => '2017-06-06 10:06:06',
        ];

        $xunBill = new XunBill();
        $xunBill->setPrivateKey('test');
        $xunBill->setOptions($options);
        $xunBill->getVerifyData();
    }

    /**
     * 測試網銀直連
     */
    public function testBankPay()
    {
        $options = [
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '1',
            'orderId' => '201709080000004557',
            'amount' => '1.00',
            'number' => '10020',
            'orderCreateDate' => '2017-06-06 10:06:06',
        ];

        $xunBill = new XunBill();
        $xunBill->setPrivateKey('test');
        $xunBill->setOptions($options);
        $encodeData = $xunBill->getVerifyData();

        $this->assertEquals($options['number'], $encodeData['pay_memberid']);
        $this->assertEquals($options['orderId'], $encodeData['pay_orderid']);
        $this->assertEquals($options['amount'], $encodeData['pay_amount']);
        $this->assertEquals($options['orderCreateDate'], $encodeData['pay_applydate']);
        $this->assertEquals('ICBC', $encodeData['pay_bankcode']);
        $this->assertEquals($options['notify_url'], $encodeData['pay_notifyurl']);
        $this->assertEquals($options['notify_url'], $encodeData['pay_callbackurl']);
        $this->assertEquals('JingDong', $encodeData['tongdao']);
        $this->assertEquals('22B45E5A2FD1D86FA580E842E6F4B374', $encodeData['pay_md5sign']);
    }

    /**
     * 測試非網銀直連
     */
    public function testPay()
    {
        $options = [
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '1090',
            'orderId' => '201709070000004556',
            'amount' => '1.00',
            'number' => '10020',
            'orderCreateDate' => '2017-06-06 10:06:06',
        ];

        $xunBill = new XunBill();
        $xunBill->setPrivateKey('test');
        $xunBill->setOptions($options);
        $encodeData = $xunBill->getVerifyData();

        $this->assertEquals($options['number'], $encodeData['pay_memberid']);
        $this->assertEquals($options['orderId'], $encodeData['pay_orderid']);
        $this->assertEquals($options['amount'], $encodeData['pay_amount']);
        $this->assertEquals($options['orderCreateDate'], $encodeData['pay_applydate']);
        $this->assertEquals('ChengYiWeiXin', $encodeData['pay_bankcode']);
        $this->assertEquals($options['notify_url'], $encodeData['pay_notifyurl']);
        $this->assertEquals($options['notify_url'], $encodeData['pay_callbackurl']);
        $this->assertEquals('ChengYiWeiXin', $encodeData['tongdao']);
        $this->assertEquals('DE9204DAECB16C897339CFEADCE18984', $encodeData['pay_md5sign']);
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

        $xunBill = new XunBill();
        $xunBill->verifyOrderPayment([]);
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

        $xunBill = new XunBill();
        $xunBill->setPrivateKey('test');
        $xunBill->verifyOrderPayment([]);
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
            'memberid' => '10020',
            'orderid' => '201709080000004557',
            'amount' => '0.010',
            'datetime' => '2017-06-05 17:17:14',
            'returncode' => '00',
            'reserved1' => '',
            'reserved2' => '',
            'reserved3' => '',
        ];

        $xunBill = new XunBill();
        $xunBill->setPrivateKey('test');
        $xunBill->setOptions($options);
        $xunBill->verifyOrderPayment([]);
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
            'memberid' => '10020',
            'orderid' => '201709080000004557',
            'amount' => '0.010',
            'datetime' => '2017-06-05 17:17:14',
            'returncode' => '00',
            'sign' => 'ILOVEEDM',
            'reserved1' => '',
            'reserved2' => '',
            'reserved3' => '',
        ];

        $xunBill = new XunBill();
        $xunBill->setPrivateKey('test');
        $xunBill->setOptions($options);
        $xunBill->verifyOrderPayment([]);
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
            'memberid' => '10020',
            'orderid' => '201709080000004557',
            'amount' => '0.010',
            'datetime' => '2017-06-05 17:17:14',
            'returncode' => '99999',
            'sign' => '7CDC4048F31E3758C808AE378F5A1C28',
            'reserved1' => '',
            'reserved2' => '',
            'reserved3' => '',
        ];

        $xunBill = new XunBill();
        $xunBill->setPrivateKey('test');
        $xunBill->setOptions($options);
        $xunBill->verifyOrderPayment([]);
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
            'memberid' => '10020',
            'orderid' => '201709080000004557',
            'amount' => '0.010',
            'datetime' => '2017-06-05 17:17:14',
            'returncode' => '00',
            'sign' => '69789A0227272704B7F653135FE0785D',
            'reserved1' => '',
            'reserved2' => '',
            'reserved3' => '',
        ];

        $entry = ['id' => '201706050000001234'];

        $xunBill = new XunBill();
        $xunBill->setPrivateKey('test');
        $xunBill->setOptions($options);
        $xunBill->verifyOrderPayment($entry);
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
            'memberid' => '10020',
            'orderid' => '201709070000004556',
            'amount' => '0.010',
            'datetime' => '2017-06-05 17:17:14',
            'returncode' => '00',
            'sign' => 'F631D877C9C5ECFC34847848E33FF0EF',
            'reserved1' => '',
            'reserved2' => '',
            'reserved3' => '',
        ];

        $entry = [
            'id' => '201709070000004556',
            'amount' => '15.00',
        ];

        $xunBill = new XunBill();
        $xunBill->setPrivateKey('test');
        $xunBill->setOptions($options);
        $xunBill->verifyOrderPayment($entry);
    }

    /**
     * 測試返回驗證成功
     */
    public function testReturnSuccess()
    {
        $options = [
            'memberid' => '10020',
            'orderid' => '201709070000004556',
            'amount' => '0.010',
            'datetime' => '2017-06-05 17:17:14',
            'returncode' => '00',
            'sign' => 'F631D877C9C5ECFC34847848E33FF0EF',
            'reserved1' => '',
            'reserved2' => '',
            'reserved3' => '',
        ];

        $entry = [
            'id' => '201709070000004556',
            'amount' => '0.01',
        ];

        $xunBill = new XunBill();
        $xunBill->setPrivateKey('test');
        $xunBill->setOptions($options);
        $xunBill->verifyOrderPayment($entry);

        $this->assertEquals('OK', $xunBill->getMsg());
    }
}
