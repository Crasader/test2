<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\PuSyun;

class PuSyunTest extends DurianTestCase
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

        $puSyun = new PuSyun();
        $puSyun->getVerifyData();
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

        $sourceData = ['number' => ''];

        $puSyun = new PuSyun();
        $puSyun->setPrivateKey('test');
        $puSyun->setOptions($sourceData);
        $puSyun->getVerifyData();
    }

    /**
     * 測試支付時代入支付平台不支援的銀行
     */
    public function testPayUnsupportedPaymentVendor()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'PaymentVendor is not supported by PaymentGateway',
            180066
        );

        $sourceData = [
            'number' => '8881343',
            'amount' => '10',
            'orderId' => '201612010000000529',
            'username' => 'php1test',
            'notify_url' => 'http://two123.comxa.com/pay/return.php',
            'paymentVendorId' => '999',
        ];

        $puSyun = new PuSyun();
        $puSyun->setPrivateKey('test');
        $puSyun->setOptions($sourceData);
        $puSyun->getVerifyData();
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $sourceData = [
            'number' => '8881343',
            'amount' => '10',
            'orderId' => '201612010000000529',
            'username' => 'php1test',
            'notify_url' => 'http://two123.comxa.com/pay/return.php',
            'paymentVendorId' => '1',
        ];

        $puSyun = new PuSyun();
        $puSyun->setPrivateKey('test');
        $puSyun->setOptions($sourceData);
        $encodeData = $puSyun->getVerifyData();

        $this->assertEquals($sourceData['number'], $encodeData['p1_MerId']);
        $this->assertEquals($sourceData['amount'], $encodeData['p3_Amt']);
        $this->assertEquals($sourceData['orderId'], $encodeData['p2_Order']);
        $this->assertEquals($sourceData['username'], $encodeData['p5_Pid']);
        $this->assertEquals($sourceData['notify_url'], $encodeData['p8_Url']);
        $this->assertEquals('ICBC-NET', $encodeData['pd_FrpId']);
        $this->assertEquals('4726ee0bf7f22c76076d0bae5195624c', $encodeData['hmac']);
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

        $puSyun = new PuSyun();
        $puSyun->verifyOrderPayment([]);
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

        $puSyun = new PuSyun();
        $puSyun->setPrivateKey('test');
        $puSyun->verifyOrderPayment([]);
    }

    /**
     * 測試返回時支付平台缺少回傳hmac
     */
    public function testReturnWithoutHmac()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $sourceData = [
            'p1_MerId' => '8881343',
            'r0_Cmd' => 'Buy',
            'r1_Code' => '1',
            'r2_TrxId' => '201612011055531999745',
            'r3_Amt' => '10',
            'r4_Cur' => 'RMB',
            'r5_Pid' => '',
            'r6_Order' => '201612010000000529',
            'r7_Uid' => '',
            'r8_MP' => '',
            'r9_BType' => '2',
        ];

        $puSyun = new PuSyun();
        $puSyun->setPrivateKey('test');
        $puSyun->setOptions($sourceData);
        $puSyun->verifyOrderPayment([]);
    }

    /**
     * 測試返回時hmac簽名驗證錯誤
     */
    public function testReturnSignatureVerificationFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Signature verification failed',
            180034
        );

        $sourceData = [
            'p1_MerId' => '8881343',
            'r0_Cmd' => 'Buy',
            'r1_Code' => '1',
            'r2_TrxId' => '201612011055531999745',
            'r3_Amt' => '10',
            'r4_Cur' => 'RMB',
            'r5_Pid' => '',
            'r6_Order' => '201612010000000529',
            'r7_Uid' => '',
            'r8_MP' => '',
            'r9_BType' => '2',
            'hmac' => '7c258616e2164f29b001e85ecad6c123',
        ];

        $puSyun = new PuSyun();
        $puSyun->setPrivateKey('test');
        $puSyun->setOptions($sourceData);
        $puSyun->verifyOrderPayment([]);
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
            'p1_MerId' => '8881343',
            'r0_Cmd' => 'Buy',
            'r1_Code' => '0',
            'r2_TrxId' => '201612011055531999745',
            'r3_Amt' => '10',
            'r4_Cur' => 'RMB',
            'r5_Pid' => '',
            'r6_Order' => '201612010000000529',
            'r7_Uid' => '',
            'r8_MP' => '',
            'r9_BType' => '2',
            'hmac' => 'e3033ecad0c9b579d3cb9e6f9f05840b',
        ];

        $puSyun = new PuSyun();
        $puSyun->setPrivateKey('test');
        $puSyun->setOptions($sourceData);
        $puSyun->verifyOrderPayment([]);
    }

    /**
     * 測試返回結果為訂單編號錯誤
     */
    public function testReturnOrderIdFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $sourceData = [
            'p1_MerId' => '8881343',
            'r0_Cmd' => 'Buy',
            'r1_Code' => '1',
            'r2_TrxId' => '201612011055531999745',
            'r3_Amt' => '10',
            'r4_Cur' => 'RMB',
            'r5_Pid' => '',
            'r6_Order' => '201612010000000529',
            'r7_Uid' => '',
            'r8_MP' => '',
            'r9_BType' => '2',
            'hmac' => '7c258616e2164f29b001e85ecad6c549',
        ];

        $entry = ['id' => '201612010000000528'];

        $puSyun = new PuSyun();
        $puSyun->setPrivateKey('test');
        $puSyun->setOptions($sourceData);
        $puSyun->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果為訂單金額錯誤
     */
    public function testReturnAmountFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $sourceData = [
            'p1_MerId' => '8881343',
            'r0_Cmd' => 'Buy',
            'r1_Code' => '1',
            'r2_TrxId' => '201612011055531999745',
            'r3_Amt' => '10',
            'r4_Cur' => 'RMB',
            'r5_Pid' => '',
            'r6_Order' => '201612010000000529',
            'r7_Uid' => '',
            'r8_MP' => '',
            'r9_BType' => '2',
            'hmac' => '7c258616e2164f29b001e85ecad6c549',
        ];

        $entry = [
            'id' => '201612010000000529',
            'amount' => '12345'
        ];

        $puSyun = new PuSyun();
        $puSyun->setPrivateKey('test');
        $puSyun->setOptions($sourceData);
        $puSyun->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testPaySuccess()
    {
        $sourceData = [
            'p1_MerId' => '8881343',
            'r0_Cmd' => 'Buy',
            'r1_Code' => '1',
            'r2_TrxId' => '201612011055531999745',
            'r3_Amt' => '10',
            'r4_Cur' => 'RMB',
            'r5_Pid' => '',
            'r6_Order' => '201612010000000529',
            'r7_Uid' => '',
            'r8_MP' => '',
            'r9_BType' => '2',
            'hmac' => '7c258616e2164f29b001e85ecad6c549',
        ];

        $entry = [
            'id' => '201612010000000529',
            'amount' => '10'
        ];

        $puSyun = new PuSyun();
        $puSyun->setPrivateKey('test');
        $puSyun->setOptions($sourceData);
        $puSyun->verifyOrderPayment($entry);

        $this->assertEquals('success', $puSyun->getMsg());
    }
}
