<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\Ylkpay;

class YlkpayTest extends DurianTestCase
{
    /**
     * 測試加密基本參數設定沒有帶入privateKey的情況
     */
    public function testEncodeWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $ylkpay = new Ylkpay();
        $ylkpay->getVerifyData();
    }

    /**
     * 測試加密基本參數設定未指定支付參數
     */
    public function testEncodeWithNoPayParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No pay parameter specified',
            180145
        );

        $ylkpay = new Ylkpay();
        $ylkpay->setPrivateKey('07qt47oVbKvC41LhAAczLk1RDJuurNjB');

        $sourceData = ['number' => ''];

        $ylkpay->setOptions($sourceData);
        $ylkpay->getVerifyData();
    }

    /**
     * 測試加密基本參數設定帶入不支援的銀行
     */
    public function testEncodeWithouttSupportBank()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'PaymentVendor is not supported by PaymentGateway',
            180066
        );

        $ylkpay = new Ylkpay();
        $ylkpay->setPrivateKey('07qt47oVbKvC41LhAAczLk1RDJuurNjB');

        $sourceData = [
            'number' => '1596',
            'orderId' => '20140625000000003',
            'amount' => '0.01',
            'notify_url' => 'http://118.232.50.208/return/return.php?pay_system=12345&hallid=6',
            'paymentVendorId' => '999',
            'merchantId' => '12345',
            'domain' => '6',
        ];

        $ylkpay->setOptions($sourceData);
        $ylkpay->getVerifyData();
    }

    /**
     * 測試加密
     */
    public function testEncode()
    {
        $sourceData = [
            'number' => '1596',
            'orderId' => '20140625000000003',
            'amount' => '0.01',
            'notify_url' => 'http://esball.org/app/member/pay_online2/pay_result.php',
            'paymentVendorId' => '1',
            'merchantId' => '12345',
            'domain' => '6',
        ];

        $ylkpay = new Ylkpay();
        $ylkpay->setPrivateKey('07qt47oVbKvC41LhAAczLk1RDJuurNjB');
        $ylkpay->setOptions($sourceData);
        $encodeData = $ylkpay->getVerifyData();

        $notifyUrl = sprintf(
            '%s?pay_system=%s&hallid=%s',
            $sourceData['notify_url'],
            $sourceData['merchantId'],
            $sourceData['domain']
        );

        $this->assertEquals($sourceData['number'], $encodeData['p1_MerId']);
        $this->assertSame('0.01', $encodeData['p3_Amt']);
        $this->assertEquals($sourceData['orderId'], $encodeData['p2_Order']);
        $this->assertEquals($notifyUrl, $encodeData['p8_Url']);
        $this->assertEquals('ICBC', $encodeData['pd_FrpId']);
        $this->assertEquals('9ab4e5b6ae94f8ee0432cd87707bf4c9', $encodeData['hmac']);
    }

    /**
     * 測試解密基本參數設定沒有帶入key的情況
     */
    public function testSetDecodeSourceNoKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $ylkpay = new Ylkpay();

        $ylkpay->verifyOrderPayment([]);
    }

    /**
     * 測試解密驗證時支付平台未指定返回參數
     */
    public function testDecodePaymentReplyWithNoReturnParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $ylkpay = new Ylkpay();
        $ylkpay->setPrivateKey('07qt47oVbKvC41LhAAczLk1RDJuurNjB');

        $sourceData = [
            'pay_system' => '12345',
            'hallid'     => '6',
            'p1_MerId'   => '1596',
            'r0_Cmd'     => 'Buy',
            'r1_Code'    => '1',
            'r2_TrxId'   => '201406250912371606',
            'r4_Cur'     => 'RMB',
            'r5_Pid'     => '',
            'r6_Order'   => '20140625000000003',
            'r7_Uid'     => '',
            'r8_MP'      => '',
            'r9_BType'   => '2',
            'rp_PayDate' => '2014-6-25 9:13:17',
            'hmac'       => '8a47ff93da16fb6afee04da0f4f8ed34'
        ];

        $ylkpay->setOptions($sourceData);
        $ylkpay->verifyOrderPayment([]);
    }

    /**
     * 測試解密驗證時支付平台沒有回傳hmac(加密簽名)
     */
    public function testDecodePaymentReplyWithoutHmac()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $ylkpay = new Ylkpay();
        $ylkpay->setPrivateKey('07qt47oVbKvC41LhAAczLk1RDJuurNjB');

        $sourceData = [
            'pay_system' => '12345',
            'hallid'     => '6',
            'p1_MerId'   => '1596',
            'r0_Cmd'     => 'Buy',
            'r1_Code'    => '1',
            'r2_TrxId'   => '201406250912371606',
            'r3_Amt'     => '0.01',
            'r4_Cur'     => 'RMB',
            'r5_Pid'     => '',
            'r6_Order'   => '20140625000000003',
            'r7_Uid'     => '',
            'r8_MP'      => '',
            'r9_BType'   => '2',
            'rp_PayDate' => '2014-6-25 9:13:17'
        ];

        $ylkpay->setOptions($sourceData);
        $ylkpay->verifyOrderPayment([]);
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

        $ylkpay = new Ylkpay();
        $ylkpay->setPrivateKey('07qt47oVbKvC41LhAAczLk1RDJuurNjB');

        $sourceData = [
            'pay_system' => '12345',
            'hallid'     => '6',
            'p1_MerId'   => '1596',
            'r0_Cmd'     => 'Buy',
            'r1_Code'    => '1',
            'r2_TrxId'   => '201406250912371606',
            'r3_Amt'     => '0.01',
            'r4_Cur'     => 'RMB',
            'r5_Pid'     => '',
            'r6_Order'   => '20140625000000003',
            'r7_Uid'     => '',
            'r8_MP'      => '',
            'r9_BType'   => '2',
            'rp_PayDate' => '2014-6-25 9:13:17',
            'hmac'       => 'afee04da0f4f8ed348a47ff93da16fb6'
        ];

        $ylkpay->setOptions($sourceData);
        $ylkpay->verifyOrderPayment([]);
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

        $ylkpay = new Ylkpay();
        $ylkpay->setPrivateKey('07qt47oVbKvC41LhAAczLk1RDJuurNjB');

        $sourceData = [
            'pay_system' => '12345',
            'hallid'     => '6',
            'p1_MerId'   => '1596',
            'r0_Cmd'     => 'Buy',
            'r1_Code'    => '0',
            'r2_TrxId'   => '201406250912371606',
            'r3_Amt'     => '0.01',
            'r4_Cur'     => 'RMB',
            'r5_Pid'     => '',
            'r6_Order'   => '20140625000000003',
            'r7_Uid'     => '',
            'r8_MP'      => '',
            'r9_BType'   => '2',
            'rp_PayDate' => '2014-6-25 9:13:17',
            'hmac'       => '6f80015b729e15e820b2aba500d6936c'
        ];

        $ylkpay->setOptions($sourceData);
        $ylkpay->verifyOrderPayment([]);
    }

    /**
     * 測試線上支付單號不正確的情況
     */
    public function testOrderIdError()
    {
        $this->setExpectedException('BB\DurianBundle\Exception\PaymentException', 'Order Id error', 180061);

        $ylkpay = new Ylkpay();
        $ylkpay->setPrivateKey('07qt47oVbKvC41LhAAczLk1RDJuurNjB');

        $sourceData = [
            'pay_system' => '12345',
            'hallid'     => '6',
            'p1_MerId'   => '1596',
            'r0_Cmd'     => 'Buy',
            'r1_Code'    => '1',
            'r2_TrxId'   => '201406250912371606',
            'r3_Amt'     => '0.01',
            'r4_Cur'     => 'RMB',
            'r5_Pid'     => '',
            'r6_Order'   => '20140625000000003',
            'r7_Uid'     => '',
            'r8_MP'      => '',
            'r9_BType'   => '2',
            'rp_PayDate' => '2014-6-25 9:13:17',
            'hmac'       => '8a47ff93da16fb6afee04da0f4f8ed34'
        ];

        $entry = ['id' => '20140625000000333'];

        $ylkpay->setOptions($sourceData);
        $ylkpay->verifyOrderPayment($entry);
    }

    /**
     * 測試線上支付金額不正確的情況
     */
    public function testOrderAmountError()
    {
        $this->setExpectedException('BB\DurianBundle\Exception\PaymentException', 'Order Amount error', 180058);

        $ylkpay = new Ylkpay();
        $ylkpay->setPrivateKey('07qt47oVbKvC41LhAAczLk1RDJuurNjB');

        $sourceData = [
            'pay_system' => '12345',
            'hallid'     => '6',
            'p1_MerId'   => '1596',
            'r0_Cmd'     => 'Buy',
            'r1_Code'    => '1',
            'r2_TrxId'   => '201406250912371606',
            'r3_Amt'     => '0.01',
            'r4_Cur'     => 'RMB',
            'r5_Pid'     => '',
            'r6_Order'   => '20140625000000003',
            'r7_Uid'     => '',
            'r8_MP'      => '',
            'r9_BType'   => '2',
            'rp_PayDate' => '2014-6-25 9:13:17',
            'hmac'       => '8a47ff93da16fb6afee04da0f4f8ed34'
        ];

        $entry = [
            'id' => '20140625000000003',
            'amount' => '1.0000'
        ];

        $ylkpay->setOptions($sourceData);
        $ylkpay->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證時PrivateKey長度超過64
     */
    public function testPayWithPrivateKeyLength()
    {
        $ylkpay = new Ylkpay();
        $ylkpay->setPrivateKey('07qt47oVbKvC41LhAAczLk1RDJuurNjB1234507qt47oVbKvC41LhAAczLk1RDJuu');

        $sourceData = [
            'pay_system' => '12345',
            'hallid'     => '6',
            'p1_MerId'   => '1596',
            'r0_Cmd'     => 'Buy',
            'r1_Code'    => '1',
            'r2_TrxId'   => '201406250912371606',
            'r3_Amt'     => '0.01',
            'r4_Cur'     => 'RMB',
            'r5_Pid'     => '',
            'r6_Order'   => '20140625000000003',
            'r7_Uid'     => '',
            'r8_MP'      => '',
            'r9_BType'   => '2',
            'rp_PayDate' => '2014-6-25 9:13:17',
            'hmac'       => '54fbb2656a42646c3e24cf7f8f8c4bc6'
        ];

        $entry = [
            'id' => '20140625000000003',
            'amount' => '0.0100'
        ];

        $ylkpay->setOptions($sourceData);
        $ylkpay->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testPaySuccess()
    {
        $ylkpay = new Ylkpay();
        $ylkpay->setPrivateKey('07qt47oVbKvC41LhAAczLk1RDJuurNjB');

        $sourceData = [
            'pay_system' => '12345',
            'hallid'     => '6',
            'p1_MerId'   => '1596',
            'r0_Cmd'     => 'Buy',
            'r1_Code'    => '1',
            'r2_TrxId'   => '201406250912371606',
            'r3_Amt'     => '0.01',
            'r4_Cur'     => 'RMB',
            'r5_Pid'     => '',
            'r6_Order'   => '20140625000000003',
            'r7_Uid'     => '',
            'r8_MP'      => '',
            'r9_BType'   => '2',
            'rp_PayDate' => '2014-6-25 9:13:17',
            'hmac'       => '8a47ff93da16fb6afee04da0f4f8ed34'
        ];

        $entry = [
            'id' => '20140625000000003',
            'amount' => '0.0100'
        ];

        $ylkpay->setOptions($sourceData);
        $ylkpay->verifyOrderPayment($entry);

        $this->assertEquals('success', $ylkpay->getMsg());
    }
}
