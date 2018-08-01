<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\YingFu;

class YingFuTest extends DurianTestCase
{
    /**
     * 測試加密基本參數設定沒有帶入privateKey的情況
     */
    public function testSetEncodeSourceNoPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $yingFu = new YingFu();
        $yingFu->getVerifyData();
    }

    /**
     * 測試加密基本參數設定未指定支付參數
     */
    public function testSetEncodeSourceNoPayParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No pay parameter specified',
            180145
        );

        $yingFu = new YingFu();
        $yingFu->setPrivateKey('1234');
        $yingFu->getVerifyData();
    }

    /**
     * 測試加密基本參數設定帶入不支援的銀行
     */
    public function testSetEncodeSourceUnsupportedPaymentVendor()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'PaymentVendor is not supported by PaymentGateway',
            180066
        );

        $sourceData = [
            'number' => '1599',
            'amount' => '0.01',
            'orderId' => '201801240000008881',
            'notify_url' => 'http://CJBBank.returnUrl.php',
            'paymentVendorId' => '999',
        ];

        $yingFu = new YingFu();
        $yingFu->setPrivateKey('1234');
        $yingFu->setOptions($sourceData);
        $yingFu->getVerifyData();
    }

    /**
     * 測試加密
     */
    public function testGetEncodeData()
    {
        $sourceData = [
            'number' => '1599',
            'amount' => '0.01',
            'orderId' => '201801240000008881',
            'notify_url' => 'http://esball.org/app/member/pay_online2/pay_result.php',
            'paymentVendorId' => '1098',
        ];

        $yingFu = new YingFu();
        $yingFu->setPrivateKey('1234');
        $yingFu->setOptions($sourceData);
        $encodeData = $yingFu->getVerifyData();

        $this->assertEquals('Buy', $encodeData['p0_Cmd']);
        $this->assertEquals($sourceData['number'], $encodeData['p1_MerId']);
        $this->assertEquals($sourceData['orderId'], $encodeData['p2_Order']);
        $this->assertEquals($sourceData['amount'], $encodeData['p3_Amt']);
        $this->assertEquals('CNY', $encodeData['p4_Cur']);
        $this->assertEquals('', $encodeData['p5_Pid']);
        $this->assertEquals('', $encodeData['p6_Pcat']);
        $this->assertEquals('', $encodeData['p7_Pdesc']);
        $this->assertEquals($sourceData['notify_url'], $encodeData['p8_Url']);
        $this->assertEquals(0, $encodeData['p9_SAF']);
        $this->assertEquals('', $encodeData['pa_MP']);
        $this->assertEquals('alipaywap', $encodeData['pd_FrpId']);
        $this->assertEquals('1', $encodeData['pr_NeedResponse']);
        $this->assertEquals('bf679d7b146eb48ff1b0879cb81cbcff', $encodeData['hmac']);
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

        $yingFu = new YingFu();

        $yingFu->verifyOrderPayment([]);
    }

    /**
     * 測試解密驗證時支付平台缺少回傳參數
     */
    public function testVerifyWithNoPayParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $sourceData = [
            'p1_MerId' => '1599',
            'r1_Code' => '1',
            'r2_TrxId' => '201401131640120001',
            'r3_Amt' => '1234.56',
            'r4_Cur' => 'RMB',
            'r5_Pid' => '',
            'r6_Order' => '20140113161012',
            'r7_Uid' => '',
            'r8_MP' => '',
            'r9_BType' => '2',
            'hmac' => 'adcfb88c80fab11ccb540eee4cd4e379',
        ];

        $yingFu = new YingFu();
        $yingFu->setPrivateKey('1234');
        $yingFu->setOptions($sourceData);
        $yingFu->verifyOrderPayment([]);
    }

    /**
     * 測試解密驗證時支付平台缺少簽名
     */
    public function testVerifyWithoutHmac()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $sourceData = [
            'p1_MerId' => '10036',
            'r0_Cmd' => 'Buy',
            'r1_Code' => '1',
            'r2_TrxId' => '201401131640120001',
            'r3_Amt' => '1234.56',
            'r4_Cur' => 'RMB',
            'r5_Pid' => '',
            'r6_Order' => '20140113161012',
            'r7_Uid' => '',
            'r8_MP' => '',
            'r9_BType' => '2',
        ];

        $yingFu = new YingFu();
        $yingFu->setPrivateKey('1234');
        $yingFu->setOptions($sourceData);
        $yingFu->verifyOrderPayment([]);
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
            'p1_MerId' => '1599',
            'r0_Cmd' => 'Buy',
            'r1_Code' => '1',
            'r2_TrxId' => '201801241348552826',
            'r3_Amt' => '0.01',
            'r4_Cur' => 'RMB',
            'r5_Pid' => '',
            'r6_Order' => '201801240000008881',
            'r7_Uid' => '',
            'r8_MP' => '',
            'r9_BType' => '2',
            'rp_PayDate' => '2018/1/24 13:49:36',
            'hmac' => '97b252d52ed773eaafe47276d1c99f4f',
        ];

        $yingFu = new YingFu();
        $yingFu->setPrivateKey('1234');
        $yingFu->setOptions($sourceData);
        $yingFu->verifyOrderPayment([]);
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
            'p1_MerId' => '1599',
            'r0_Cmd' => 'Buy',
            'r1_Code' => '99',
            'r2_TrxId' => '201801241348552826',
            'r3_Amt' => '0.01',
            'r4_Cur' => 'RMB',
            'r5_Pid' => '',
            'r6_Order' => '201801240000008881',
            'r7_Uid' => '',
            'r8_MP' => '',
            'r9_BType' => '2',
            'rp_PayDate' => '2018/1/24 13:49:36',
            'hmac' => '4d3c97b16366bdce3399d17667ca1a86',
        ];

        $yingFu = new YingFu();
        $yingFu->setPrivateKey('1234');
        $yingFu->setOptions($sourceData);
        $yingFu->verifyOrderPayment([]);
    }

    /**
     * 測試線上支付單號不正確的情況
     */
    public function testOrderIdError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $sourceData = [
            'p1_MerId' => '1599',
            'r0_Cmd' => 'Buy',
            'r1_Code' => '1',
            'r2_TrxId' => '201801241348552826',
            'r3_Amt' => '0.01',
            'r4_Cur' => 'RMB',
            'r5_Pid' => '',
            'r6_Order' => '201801240000008881',
            'r7_Uid' => '',
            'r8_MP' => '',
            'r9_BType' => '2',
            'rp_PayDate' => '2018/1/24 13:49:36',
            'hmac' => '0ff20fbf3b7d12c162345f77c5dec0c6',
        ];

        $entry = ['id' => '201801240000000000'];

        $yingFu = new YingFu();
        $yingFu->setPrivateKey('1234');
        $yingFu->setOptions($sourceData);
        $yingFu->verifyOrderPayment($entry);
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
            'p1_MerId' => '1599',
            'r0_Cmd' => 'Buy',
            'r1_Code' => '1',
            'r2_TrxId' => '201801241348552826',
            'r3_Amt' => '0.01',
            'r4_Cur' => 'RMB',
            'r5_Pid' => '',
            'r6_Order' => '201801240000008881',
            'r7_Uid' => '',
            'r8_MP' => '',
            'r9_BType' => '2',
            'rp_PayDate' => '2018/1/24 13:49:36',
            'hmac' => '0ff20fbf3b7d12c162345f77c5dec0c6',
        ];

        $entry = [
            'id' => '201801240000008881',
            'amount' => '12345.6000',
        ];

        $yingFu = new YingFu();
        $yingFu->setPrivateKey('1234');
        $yingFu->setOptions($sourceData);
        $yingFu->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testPaySuccess()
    {
        $sourceData = [
            'p1_MerId' => '1599',
            'r0_Cmd' => 'Buy',
            'r1_Code' => '1',
            'r2_TrxId' => '201801241348552826',
            'r3_Amt' => '0.01',
            'r4_Cur' => 'RMB',
            'r5_Pid' => '',
            'r6_Order' => '201801240000008881',
            'r7_Uid' => '',
            'r8_MP' => '',
            'r9_BType' => '2',
            'rp_PayDate' => '2018/1/24 13:49:36',
            'hmac' => '0ff20fbf3b7d12c162345f77c5dec0c6',
        ];

        $entry = [
            'id' => '201801240000008881',
            'amount' => '0.01',
        ];

        $yingFu = new YingFu();
        $yingFu->setPrivateKey('1234');
        $yingFu->setOptions($sourceData);
        $yingFu->verifyOrderPayment($entry);

        $this->assertEquals('success', $yingFu->getMsg());
    }
}
