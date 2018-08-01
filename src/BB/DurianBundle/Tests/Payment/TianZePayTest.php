<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\TianZePay;

class TianZePayTest extends DurianTestCase
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

        $tianZePay = new TianZePay();
        $tianZePay->getVerifyData();
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

        $tianZePay = new TianZePay();
        $tianZePay->setPrivateKey('test');
        $tianZePay->setOptions($sourceData);
        $tianZePay->getVerifyData();
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
            'postUrl' => 'http://101.132.128.241/GateWay/',
            'number' => '8881343',
            'amount' => '10',
            'orderId' => '201612010000000529',
            'username' => 'php1test',
            'notify_url' => 'http://two123.comxa.com/pay/return.php',
            'paymentVendorId' => '999',
        ];

        $tianZePay = new TianZePay();
        $tianZePay->setPrivateKey('test');
        $tianZePay->setOptions($sourceData);
        $tianZePay->getVerifyData();
    }

    /**
     * 測試PC收銀台支付
     */
    public function testPcPay()
    {
        $sourceData = [
            'postUrl' => 'http://101.132.128.241/GateWay/',
            'number' => '8881343',
            'amount' => '10',
            'orderId' => '201612010000000529',
            'username' => 'php1test',
            'notify_url' => 'http://two123.comxa.com/pay/return.php',
            'paymentVendorId' => '1102',
        ];

        $tianZePay = new TianZePay();
        $tianZePay->setPrivateKey('test');
        $tianZePay->setOptions($sourceData);
        $encodeData = $tianZePay->getVerifyData();

        $this->assertEquals('http://101.132.128.241/GateWay/ReceiveBank.aspx', $encodeData['post_url']);
        $this->assertEquals('Buy', $encodeData['params']['p0_Cmd']);
        $this->assertEquals($sourceData['number'], $encodeData['params']['p1_MerId']);
        $this->assertEquals($sourceData['orderId'], $encodeData['params']['p2_Order']);
        $this->assertEquals($sourceData['amount'], $encodeData['params']['p3_Amt']);
        $this->assertEquals('CNY', $encodeData['params']['p4_Cur']);
        $this->assertEquals($sourceData['username'], $encodeData['params']['p5_Pid']);
        $this->assertEquals('', $encodeData['params']['p6_Pcat']);
        $this->assertEquals('', $encodeData['params']['p7_Pdesc']);
        $this->assertEquals($sourceData['notify_url'], $encodeData['params']['p8_Url']);
        $this->assertEquals('0', $encodeData['params']['p9_SAF']);
        $this->assertEquals('paydesk', $encodeData['params']['pd_FrpId']);
        $this->assertEquals('1d5c3f5c9032d1ab712f3def305d39cb', $encodeData['params']['hmac']);
    }

    /**
     * 測試二維收銀台支付
     */
    public function testScanPay()
    {
        $sourceData = [
            'postUrl' => 'http://101.132.128.241/GateWay/',
            'number' => '8881343',
            'amount' => '10',
            'orderId' => '201612010000000529',
            'username' => 'php1test',
            'notify_url' => 'http://two123.comxa.com/pay/return.php',
            'paymentVendorId' => '1113',
        ];

        $tianZePay = new TianZePay();
        $tianZePay->setPrivateKey('test');
        $tianZePay->setOptions($sourceData);
        $encodeData = $tianZePay->getVerifyData();

        $this->assertEquals('http://101.132.128.241/GateWay/ReceiveBank.aspx', $encodeData['post_url']);
        $this->assertEquals('Buy', $encodeData['params']['p0_Cmd']);
        $this->assertEquals($sourceData['number'], $encodeData['params']['p1_MerId']);
        $this->assertEquals($sourceData['orderId'], $encodeData['params']['p2_Order']);
        $this->assertEquals($sourceData['amount'], $encodeData['params']['p3_Amt']);
        $this->assertEquals('CNY', $encodeData['params']['p4_Cur']);
        $this->assertEquals($sourceData['username'], $encodeData['params']['p5_Pid']);
        $this->assertEquals('', $encodeData['params']['p6_Pcat']);
        $this->assertEquals('', $encodeData['params']['p7_Pdesc']);
        $this->assertEquals($sourceData['notify_url'], $encodeData['params']['p8_Url']);
        $this->assertEquals('0', $encodeData['params']['p9_SAF']);
        $this->assertEquals('paydesk', $encodeData['params']['pd_FrpId']);
        $this->assertEquals('1d5c3f5c9032d1ab712f3def305d39cb', $encodeData['params']['hmac']);
    }

    /**
     * 測試WAP收銀台支付
     */
    public function testWapPay()
    {
        $sourceData = [
            'postUrl' => 'http://101.132.128.241/GateWay/',
            'number' => '8881343',
            'amount' => '10',
            'orderId' => '201612010000000529',
            'username' => 'php1test',
            'notify_url' => 'http://two123.comxa.com/pay/return.php',
            'paymentVendorId' => '1100',
        ];

        $tianZePay = new TianZePay();
        $tianZePay->setPrivateKey('test');
        $tianZePay->setOptions($sourceData);
        $encodeData = $tianZePay->getVerifyData();

        $this->assertEquals('http://101.132.128.241/GateWay/ReceiveBankmobile.aspx', $encodeData['post_url']);
        $this->assertEquals('Buy', $encodeData['params']['p0_Cmd']);
        $this->assertEquals($sourceData['number'], $encodeData['params']['p1_MerId']);
        $this->assertEquals($sourceData['orderId'], $encodeData['params']['p2_Order']);
        $this->assertEquals($sourceData['amount'], $encodeData['params']['p3_Amt']);
        $this->assertEquals('CNY', $encodeData['params']['p4_Cur']);
        $this->assertEquals($sourceData['username'], $encodeData['params']['p5_Pid']);
        $this->assertEquals('', $encodeData['params']['p6_Pcat']);
        $this->assertEquals('', $encodeData['params']['p7_Pdesc']);
        $this->assertEquals($sourceData['notify_url'], $encodeData['params']['p8_Url']);
        $this->assertEquals('0', $encodeData['params']['p9_SAF']);
        $this->assertEquals('paydesk', $encodeData['params']['pd_FrpId']);
        $this->assertEquals('1d5c3f5c9032d1ab712f3def305d39cb', $encodeData['params']['hmac']);
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

        $tianZePay = new TianZePay();
        $tianZePay->verifyOrderPayment([]);
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

        $tianZePay = new TianZePay();
        $tianZePay->setPrivateKey('test');
        $tianZePay->verifyOrderPayment([]);
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

        $tianZePay = new TianZePay();
        $tianZePay->setPrivateKey('test');
        $tianZePay->setOptions($sourceData);
        $tianZePay->verifyOrderPayment([]);
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

        $tianZePay = new TianZePay();
        $tianZePay->setPrivateKey('test');
        $tianZePay->setOptions($sourceData);
        $tianZePay->verifyOrderPayment([]);
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

        $tianZePay = new TianZePay();
        $tianZePay->setPrivateKey('test');
        $tianZePay->setOptions($sourceData);
        $tianZePay->verifyOrderPayment([]);
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

        $tianZePay = new TianZePay();
        $tianZePay->setPrivateKey('test');
        $tianZePay->setOptions($sourceData);
        $tianZePay->verifyOrderPayment($entry);
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
            'amount' => '12345',
        ];

        $tianZePay = new TianZePay();
        $tianZePay->setPrivateKey('test');
        $tianZePay->setOptions($sourceData);
        $tianZePay->verifyOrderPayment($entry);
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
            'amount' => '10',
        ];

        $tianZePay = new TianZePay();
        $tianZePay->setPrivateKey('test');
        $tianZePay->setOptions($sourceData);
        $tianZePay->verifyOrderPayment($entry);

        $this->assertEquals('success', $tianZePay->getMsg());
    }
}
