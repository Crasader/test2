<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\HuiFuHengTung;

class HuiFuHengTungTest extends DurianTestCase
{
    /**
     * 測試支付時沒有帶入privateKey的情況
     */
    public function testPayNoPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $huiFuHengTung = new HuiFuHengTung();
        $huiFuHengTung->getVerifyData();
    }

    /**
     * 測試支付時未指定支付參數
     */
    public function testPayNoPayParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No pay parameter specified',
            180145
        );

        $sourceData = ['number' => ''];

        $huiFuHengTung = new HuiFuHengTung();
        $huiFuHengTung->setPrivateKey('1234');
        $huiFuHengTung->setOptions($sourceData);
        $huiFuHengTung->getVerifyData();
    }

    /**
     * 測試支付時帶入不支援的銀行
     */
    public function testPayNotSupportBank()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'PaymentVendor is not supported by PaymentGateway',
            180066
        );

        $sourceData = [
            'number' => '80087',
            'orderId' => '201711070000007465',
            'amount' => '1',
            'notify_url' => 'http://pay.my/pay/return.php',
            'paymentVendorId' => '999',
            'username' => 'php1test',
        ];

        $huiFuHengTung = new HuiFuHengTung();
        $huiFuHengTung->setPrivateKey('1234');
        $huiFuHengTung->setOptions($sourceData);
        $huiFuHengTung->getVerifyData();
    }

    /**
     * 測試支付時PrivateKey長度超過64
     */
    public function testPayWithPrivateKeyLength()
    {
        $sourceData = [
            'number' => '80087',
            'orderId' => '201711070000007465',
            'amount' => '1',
            'notify_url' => 'http://pay.my/pay/return.php',
            'paymentVendorId' => '1',
            'username' => 'php1test',
        ];

        $huiFuHengTung = new HuiFuHengTung();
        $huiFuHengTung->setPrivateKey('Xke1a0TqZE2Q8ku943LgC425247y3zuu09vLh78S4U0142rp60h3s6egNY3j12345');
        $huiFuHengTung->setOptions($sourceData);
        $huiFuHengTung->getVerifyData();
    }

    /**
     * 測試加密
     */
    public function testPay()
    {
        $sourceData = [
            'number' => '80087',
            'orderId' => '201711070000007465',
            'amount' => '1',
            'notify_url' => 'http://pay.my/pay/return.php',
            'paymentVendorId' => '1',
            'username' => 'php1test',
        ];

        $huiFuHengTung = new HuiFuHengTung();
        $huiFuHengTung->setPrivateKey('1234');
        $huiFuHengTung->setOptions($sourceData);
        $encodeData = $huiFuHengTung->getVerifyData();

        $this->assertEquals('Buy', $encodeData['p0_Cmd']);
        $this->assertEquals($sourceData['number'], $encodeData['p1_MerId']);
        $this->assertEquals($sourceData['orderId'], $encodeData['p2_Order']);
        $this->assertEquals('1.00', $encodeData['p3_Amt']);
        $this->assertEquals('CNY', $encodeData['p4_Cur']);
        $this->assertEquals('php1test', $encodeData['p5_Pid']);
        $this->assertEquals(0, $encodeData['p6_Pcat']);
        $this->assertEquals(0, $encodeData['p7_Pdesc']);
        $this->assertEquals($sourceData['notify_url'], $encodeData['p8_Url']);
        $this->assertEquals('ICBC-NET-B2C', $encodeData['pd_FrpId']);
        $this->assertEquals('1', $encodeData['pr_NeedResponse']);
        $this->assertEquals('a21b62a5bb16381b9a4d1f6737890b05', $encodeData['hmac']);
    }

    /**
     * 測試返回時沒有帶入key的情況
     */
    public function testReturnWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $huiFuHengTung = new HuiFuHengTung();
        $huiFuHengTung->verifyOrderPayment([]);
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

        $sourceData = ['p1_MerId' => '80087',];

        $huiFuHengTung = new HuiFuHengTung();
        $huiFuHengTung->setPrivateKey('1234');
        $huiFuHengTung->setOptions($sourceData);
        $huiFuHengTung->verifyOrderPayment([]);
    }

    /**
     * 測試返回時沒有回傳hmac(加密簽名)
     */
    public function testReturnWithoutHmac()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $sourceData = [
            'p1_MerId' => '9900189',
            'r0_Cmd' => 'Buy',
            'r1_Code' => '1',
            'r2_TrxId' => 'O201711071201589900189',
            'r3_Amt' => '0.010',
            'r4_Cur' => 'CNY',
            'r5_Pid' => 'php1test',
            'r6_Order' => '201711070000007465',
            'r7_Uid' => '',
            'r8_MP' => 'php1test',
            'r9_BType' => '2',
            'rb_BankId' => 'ICBC-NET-B2C',
            'ro_BankOrderId' => 'null',
            'rp_PayDate' => '2017-11-07',
            'rq_CardNo' => '',
            'ru_Trxtime' => '2017-11-07',
        ];

        $huiFuHengTung = new HuiFuHengTung();
        $huiFuHengTung->setPrivateKey('1234');
        $huiFuHengTung->setOptions($sourceData);
        $huiFuHengTung->verifyOrderPayment([]);
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
            'p1_MerId' => '9900189',
            'r0_Cmd' => 'Buy',
            'r1_Code' => '1',
            'r2_TrxId' => 'O201711071201589900189',
            'r3_Amt' => '0.010',
            'r4_Cur' => 'CNY',
            'r5_Pid' => 'php1test',
            'r6_Order' => '201711070000007465',
            'r7_Uid' => '',
            'r8_MP' => 'php1test',
            'r9_BType' => '2',
            'rb_BankId' => 'ICBC-NET-B2C',
            'ro_BankOrderId' => 'null',
            'rp_PayDate' => '2017-11-07',
            'rq_CardNo' => '',
            'ru_Trxtime' => '2017-11-07',
            'hmac' => 'd288f21abbc8610657dec10e5bb8d83a',
        ];

        $huiFuHengTung = new HuiFuHengTung();
        $huiFuHengTung->setPrivateKey('1234');
        $huiFuHengTung->setOptions($sourceData);
        $huiFuHengTung->verifyOrderPayment([]);
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
            'p1_MerId' => '9900189',
            'r0_Cmd' => 'Buy',
            'r1_Code' => '9',
            'r2_TrxId' => 'O201711071201589900189',
            'r3_Amt' => '0.010',
            'r4_Cur' => 'CNY',
            'r5_Pid' => 'php1test',
            'r6_Order' => '201711070000007465',
            'r7_Uid' => '',
            'r8_MP' => 'php1test',
            'r9_BType' => '2',
            'rb_BankId' => 'ICBC-NET-B2C',
            'ro_BankOrderId' => 'null',
            'rp_PayDate' => '2017-11-07',
            'rq_CardNo' => '',
            'ru_Trxtime' => '2017-11-07',
            'hmac' => '6b3a07c104a85ded7d6a3581dfd07392',
        ];

        $huiFuHengTung = new HuiFuHengTung();
        $huiFuHengTung->setPrivateKey('1234');
        $huiFuHengTung->setOptions($sourceData);
        $huiFuHengTung->verifyOrderPayment([]);
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
            'p1_MerId' => '9900189',
            'r0_Cmd' => 'Buy',
            'r1_Code' => '1',
            'r2_TrxId' => 'O201711071201589900189',
            'r3_Amt' => '0.010',
            'r4_Cur' => 'CNY',
            'r5_Pid' => 'php1test',
            'r6_Order' => '201711070000007465',
            'r7_Uid' => '',
            'r8_MP' => 'php1test',
            'r9_BType' => '2',
            'rb_BankId' => 'ICBC-NET-B2C',
            'ro_BankOrderId' => 'null',
            'rp_PayDate' => '2017-11-07',
            'rq_CardNo' => '',
            'ru_Trxtime' => '2017-11-07',
            'hmac' => '74fd4557d13ec391c77cf32ab6fefd8f',
        ];

        $entry = ['id' => '201405020016748610'];

        $huiFuHengTung = new HuiFuHengTung();
        $huiFuHengTung->setPrivateKey('1234');
        $huiFuHengTung->setOptions($sourceData);
        $huiFuHengTung->verifyOrderPayment($entry);
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
            'p1_MerId' => '9900189',
            'r0_Cmd' => 'Buy',
            'r1_Code' => '1',
            'r2_TrxId' => 'O201711071201589900189',
            'r3_Amt' => '0.010',
            'r4_Cur' => 'CNY',
            'r5_Pid' => 'php1test',
            'r6_Order' => '201711070000007465',
            'r7_Uid' => '',
            'r8_MP' => 'php1test',
            'r9_BType' => '2',
            'rb_BankId' => 'ICBC-NET-B2C',
            'ro_BankOrderId' => 'null',
            'rp_PayDate' => '2017-11-07',
            'rq_CardNo' => '',
            'ru_Trxtime' => '2017-11-07',
            'hmac' => '74fd4557d13ec391c77cf32ab6fefd8f',
        ];

        $entry = [
            'id' => '201711070000007465',
            'amount' => '9900.0000',
        ];

        $huiFuHengTung = new HuiFuHengTung();
        $huiFuHengTung->setPrivateKey('1234');
        $huiFuHengTung->setOptions($sourceData);
        $huiFuHengTung->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testReturnSuccess()
    {
        $sourceData = [
            'p1_MerId' => '9900189',
            'r0_Cmd' => 'Buy',
            'r1_Code' => '1',
            'r2_TrxId' => 'O201711071201589900189',
            'r3_Amt' => '0.010',
            'r4_Cur' => 'CNY',
            'r5_Pid' => 'php1test',
            'r6_Order' => '201711070000007465',
            'r7_Uid' => '',
            'r8_MP' => 'php1test',
            'r9_BType' => '2',
            'rb_BankId' => 'ICBC-NET-B2C',
            'ro_BankOrderId' => 'null',
            'rp_PayDate' => '2017-11-07',
            'rq_CardNo' => '',
            'ru_Trxtime' => '2017-11-07',
            'hmac' => '74fd4557d13ec391c77cf32ab6fefd8f',
        ];

        $entry = [
            'id' => '201711070000007465',
            'amount' => '0.0100',
        ];

        $huiFuHengTung = new HuiFuHengTung();
        $huiFuHengTung->setPrivateKey('1234');
        $huiFuHengTung->setOptions($sourceData);
        $huiFuHengTung->verifyOrderPayment($entry);

        $this->assertEquals('success', $huiFuHengTung->getMsg());
    }
}
