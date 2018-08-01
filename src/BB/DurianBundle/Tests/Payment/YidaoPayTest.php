<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\YidaoPay;

class YidaoPayTest extends DurianTestCase
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

        $yidaoPay = new YidaoPay();
        $yidaoPay->getVerifyData();
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

        $yidaoPay = new YidaoPay();
        $yidaoPay->setPrivateKey('1234');
        $yidaoPay->setOptions($sourceData);
        $yidaoPay->getVerifyData();
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
            'orderId' => '201710300000007435',
            'amount' => '1',
            'notify_url' => 'http://pay.my/pay/return.php',
            'paymentVendorId' => '999',
            'username' => 'php1test',
        ];

        $yidaoPay = new YidaoPay();
        $yidaoPay->setPrivateKey('1234');
        $yidaoPay->setOptions($sourceData);
        $yidaoPay->getVerifyData();
    }

    /**
     * 測試支付時PrivateKey長度超過64
     */
    public function testPayWithPrivateKeyLength()
    {
        $sourceData = [
            'number' => '80087',
            'orderId' => '201710300000007435',
            'amount' => '1',
            'notify_url' => 'http://pay.my/pay/return.php',
            'paymentVendorId' => '1',
            'username' => 'php1test',
        ];

        $yidaoPay = new YidaoPay();
        $yidaoPay->setPrivateKey('Xke1a0TqZE2Q8ku943LgC425247y3zuu09vLh78S4U0142rp60h3s6egNY3j12345');
        $yidaoPay->setOptions($sourceData);
        $yidaoPay->getVerifyData();
    }

    /**
     * 測試加密
     */
    public function testPay()
    {
        $sourceData = [
            'number' => '80087',
            'orderId' => '201710300000007435',
            'amount' => '1',
            'notify_url' => 'http://pay.my/pay/return.php',
            'paymentVendorId' => '1',
            'username' => 'php1test',
        ];

        $yidaoPay = new YidaoPay();
        $yidaoPay->setPrivateKey('1234');
        $yidaoPay->setOptions($sourceData);
        $encodeData = $yidaoPay->getVerifyData();

        $this->assertEquals('Buy', $encodeData['p0_Cmd']);
        $this->assertEquals($sourceData['number'], $encodeData['p1_MerId']);
        $this->assertEquals($sourceData['orderId'], $encodeData['p2_Order']);
        $this->assertSame('1.00', $encodeData['p3_Amt']);
        $this->assertSame('CNY', $encodeData['p4_Cur']);
        $this->assertSame('php1test', $encodeData['p5_Pid']);
        $this->assertSame('', $encodeData['p6_Pcat']);
        $this->assertSame('', $encodeData['p7_Pdesc']);
        $this->assertEquals($sourceData['notify_url'], $encodeData['p8_Url']);
        $this->assertSame('', $encodeData['pa_MP']);
        $this->assertEquals('ICBC-NET-B2C', $encodeData['pd_FrpId']);
        $this->assertSame('1', $encodeData['pr_NeedResponse']);
        $this->assertEquals('ccbc45e28527b11ba1e551fae5063ec7', $encodeData['hmac']);
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

        $yidaoPay = new YidaoPay();
        $yidaoPay->verifyOrderPayment([]);
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

        $yidaoPay = new YidaoPay();
        $yidaoPay->setPrivateKey('1234');
        $yidaoPay->setOptions($sourceData);
        $yidaoPay->verifyOrderPayment([]);
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
            'p1_MerId' => '80087',
            'r0_Cmd' => 'Buy',
            'r1_Code' => '1',
            'r2_TrxId' => '201710301637526184425',
            'r3_Amt' => '1.00',
            'r4_Cur' => 'RMB',
            'r5_Pid' => '',
            'r6_Order' => '201710300000007435',
            'r7_Uid' => '',
            'r8_MP' => 'php1test',
            'r9_BType' => '2',
            'rb_BankId' => '',
            'ro_BankOrderId' => '',
            'rp_PayDate' => '2017-10-30 16:38:20',
            'rq_CardNo' => '',
            'ru_Trxtime' => '2017-10-30 16:38:20',
        ];

        $yidaoPay = new YidaoPay();
        $yidaoPay->setPrivateKey('1234');
        $yidaoPay->setOptions($sourceData);
        $yidaoPay->verifyOrderPayment([]);
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
            'p1_MerId' => '80087',
            'r0_Cmd' => 'Buy',
            'r1_Code' => '1',
            'r2_TrxId' => '201710301637526184425',
            'r3_Amt' => '1.00',
            'r4_Cur' => 'RMB',
            'r5_Pid' => '',
            'r6_Order' => '201710300000007435',
            'r7_Uid' => '',
            'r8_MP' => 'php1test',
            'r9_BType' => '2',
            'rb_BankId' => '',
            'ro_BankOrderId' => '',
            'rp_PayDate' => '2017-10-30 16:38:20',
            'rq_CardNo' => '',
            'ru_Trxtime' => '2017-10-30 16:38:20',
            'hmac' => 'c6df78b2fd6151a4ce2784ba91881dbd',
        ];

        $yidaoPay = new YidaoPay();
        $yidaoPay->setPrivateKey('1234');
        $yidaoPay->setOptions($sourceData);
        $yidaoPay->verifyOrderPayment([]);
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
            'p1_MerId' => '80087',
            'r0_Cmd' => 'Buy',
            'r1_Code' => '5',
            'r2_TrxId' => '201710301637526184425',
            'r3_Amt' => '1.00',
            'r4_Cur' => 'RMB',
            'r5_Pid' => '',
            'r6_Order' => '201710300000007435',
            'r7_Uid' => '',
            'r8_MP' => 'php1test',
            'r9_BType' => '2',
            'rb_BankId' => '',
            'ro_BankOrderId' => '',
            'rp_PayDate' => '2017-10-30 16:38:20',
            'rq_CardNo' => '',
            'ru_Trxtime' => '2017-10-30 16:38:20',
            'hmac' => '2b6c4ffc98e2e974f0717b726d496fda',
        ];

        $yidaoPay = new YidaoPay();
        $yidaoPay->setPrivateKey('1234');
        $yidaoPay->setOptions($sourceData);
        $yidaoPay->verifyOrderPayment([]);
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
            'p1_MerId' => '80087',
            'r0_Cmd' => 'Buy',
            'r1_Code' => '1',
            'r2_TrxId' => '201710301637526184425',
            'r3_Amt' => '1.00',
            'r4_Cur' => 'RMB',
            'r5_Pid' => '',
            'r6_Order' => '201710300000007435',
            'r7_Uid' => '',
            'r8_MP' => 'php1test',
            'r9_BType' => '2',
            'rb_BankId' => '',
            'ro_BankOrderId' => '',
            'rp_PayDate' => '2017-10-30 16:38:20',
            'rq_CardNo' => '',
            'ru_Trxtime' => '2017-10-30 16:38:20',
            'hmac' => '79b26d78775cae47dc888d76c39b10f5',
        ];

        $entry = ['id' => '201405020016748610'];

        $yidaoPay = new YidaoPay();
        $yidaoPay->setPrivateKey('1234');
        $yidaoPay->setOptions($sourceData);
        $yidaoPay->verifyOrderPayment($entry);
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
            'p1_MerId' => '80087',
            'r0_Cmd' => 'Buy',
            'r1_Code' => '1',
            'r2_TrxId' => '201710301637526184425',
            'r3_Amt' => '1.00',
            'r4_Cur' => 'RMB',
            'r5_Pid' => '',
            'r6_Order' => '201710300000007435',
            'r7_Uid' => '',
            'r8_MP' => 'php1test',
            'r9_BType' => '2',
            'rb_BankId' => '',
            'ro_BankOrderId' => '',
            'rp_PayDate' => '2017-10-30 16:38:20',
            'rq_CardNo' => '',
            'ru_Trxtime' => '2017-10-30 16:38:20',
            'hmac' => '79b26d78775cae47dc888d76c39b10f5',
        ];

        $entry = [
            'id' => '201710300000007435',
            'amount' => '9900.0000'
        ];

        $yidaoPay = new YidaoPay();
        $yidaoPay->setPrivateKey('1234');
        $yidaoPay->setOptions($sourceData);
        $yidaoPay->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testReturnSuccess()
    {
        $sourceData = [
            'p1_MerId' => '80087',
            'r0_Cmd' => 'Buy',
            'r1_Code' => '1',
            'r2_TrxId' => '201710301637526184425',
            'r3_Amt' => '1.00',
            'r4_Cur' => 'RMB',
            'r5_Pid' => '',
            'r6_Order' => '201710300000007435',
            'r7_Uid' => '',
            'r8_MP' => 'php1test',
            'r9_BType' => '2',
            'rb_BankId' => '',
            'ro_BankOrderId' => '',
            'rp_PayDate' => '2017-10-30 16:38:20',
            'rq_CardNo' => '',
            'ru_Trxtime' => '2017-10-30 16:38:20',
            'hmac' => '79b26d78775cae47dc888d76c39b10f5',
        ];

        $entry = [
            'id' => '201710300000007435',
            'amount' => '1.0000'
        ];

        $yidaoPay = new YidaoPay();
        $yidaoPay->setPrivateKey('1234');
        $yidaoPay->setOptions($sourceData);
        $yidaoPay->verifyOrderPayment($entry);

        $this->assertEquals('success', $yidaoPay->getMsg());
    }
}
