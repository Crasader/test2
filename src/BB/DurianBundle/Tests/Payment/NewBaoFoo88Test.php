<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\NewBaoFoo88;

class NewBaoFoo88Test extends DurianTestCase
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

        $newBaoFoo88 = new NewBaoFoo88();
        $newBaoFoo88->getVerifyData();
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

        $newBaoFoo88 = new NewBaoFoo88();
        $newBaoFoo88->setPrivateKey('QJ8vydVo340NAWk3r5tf3sTuuj0Mzm26');
        $newBaoFoo88->getVerifyData();
    }

    /**
     * 測試支付設定帶入不支援的銀行
     */
    public function testPayWithPaymentVendorIsNotSupport()

    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'PaymentVendor is not supported by PaymentGateway',
            180066
        );

        $options = [
            'number' => '20130809',
            'orderId' => '201503160000002219',
            'amount' => '100',
            'notify_url' => 'http://118.233.206.129:8080/return.php',
            'paymentVendorId' => '99999',
            'merchantId' => '12345',
            'domain' => '6',
        ];

        $newBaoFoo88 = new NewBaoFoo88();
        $newBaoFoo88->setPrivateKey('QJ8vydVo340NAWk3r5tf3sTuuj0Mzm26');
        $newBaoFoo88->setOptions($options);
        $newBaoFoo88->getVerifyData();
    }

    /**
     * 測試加密
     */
    public function testPay()
    {
        $options = [
            'number' => '1565',
            'orderId' => '201505110000000093',
            'amount' => '0.01',
            'notify_url' => 'http://118.233.206.129:8080/return.php',
            'paymentVendorId' => '1',
            'merchantId' => '12345',
            'domain' => '6',
        ];

        $newBaoFoo88 = new NewBaoFoo88();
        $newBaoFoo88->setPrivateKey('QQJ8vydVo340NAWk3r5tf3sTuuj0Mzm26QJ8vydVo340NAWk3r5tf3sTuuj0Mzm26');
        $newBaoFoo88->setOptions($options);
        $requestData = $newBaoFoo88->getVerifyData();

        $this->assertTrue('0.01' === $requestData['p3_Amt']);
        $this->assertEquals($options['number'], $requestData['p1_MerId']);
        $this->assertEquals($options['orderId'], $requestData['p2_Order']);
        $this->assertEquals($options['notify_url'], $requestData['p8_Url']);
        $this->assertEquals('ICBC', $requestData['pd_FrpId']);
        $this->assertEquals('33ee475e375b17b465aa83a0b4b94c6b', $requestData['hmac']);
    }

    /**
     * 測試加密，使用微信二維
     */
    public function testPayWithWeixin()
    {
        $options = [
            'number' => '1565',
            'orderId' => '201505110000000093',
            'amount' => '0.01',
            'notify_url' => 'http://118.233.206.129:8080/return.php',
            'postUrl' => 'http://www.weijie888.net/GateWay/ReceiveBank.aspx',
            'paymentVendorId' => '1090',
            'merchantId' => '12345',
            'domain' => '6',
        ];

        $newBaoFoo88 = new NewBaoFoo88();
        $newBaoFoo88->setPrivateKey('QQJ8vydVo340NAWk3r5tf3sTuuj0Mzm26QJ8vydVo340NAWk3r5tf3sTuuj0Mzm26');
        $newBaoFoo88->setOptions($options);
        $requestData = $newBaoFoo88->getVerifyData();

        $this->assertEquals('http://www.weijie888.net/GateWay/ReceiveWX.aspx', $requestData['post_url']);
        $this->assertTrue('0.01' === $requestData['params']['p3_Amt']);
        $this->assertEquals($options['number'], $requestData['params']['p1_MerId']);
        $this->assertEquals($options['orderId'], $requestData['params']['p2_Order']);
        $this->assertEquals($options['notify_url'], $requestData['params']['p8_Url']);
        $this->assertEquals('WX-NET', $requestData['params']['pd_FrpId']);
        $this->assertEquals('8176fe975620ecdc6ce4c8506d5b4c53', $requestData['params']['hmac']);
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

        $newBaoFoo88 = new NewBaoFoo88();
        $newBaoFoo88->verifyOrderPayment([]);
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

        $newBaoFoo88 = new NewBaoFoo88();
        $newBaoFoo88->setPrivateKey('QJ8vydVo340NAWk3r5tf3sTuuj0Mzm26');
        $newBaoFoo88->verifyOrderPayment([]);
    }

    /**
     * 測試返回時缺少hmac
     */
    public function testReturnWithoutHmac()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $options = [
            'p1_MerId' => '1565',
            'r0_Cmd' => 'Buy',
            'r1_Code' => '1',
            'r2_TrxId' => '201505120041006041',
            'r3_Amt' => '0.01',
            'r4_Cur' => 'RMB',
            'r5_Pid' => '',
            'r6_Order' => '201505110000000093',
            'r7_Uid' => '',
            'r8_MP' => '',
            'r9_BType' => '2',
            'rp_PayDate' => '2015-5-12 0:42:22',
        ];

        $newBaoFoo88 = new NewBaoFoo88();
        $newBaoFoo88->setPrivateKey('QJ8vydVo340NAWk3r5tf3sTuuj0Mzm26');
        $newBaoFoo88->setOptions($options);
        $newBaoFoo88->verifyOrderPayment($options);
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
            'pay_system' => '100025',
            'hallid' => '6',
            'p1_MerId' => '1565',
            'r0_Cmd' => 'Buy',
            'r1_Code' => '1',
            'r2_TrxId' => '201505120041006041',
            'r3_Amt' => '0.01',
            'r4_Cur' => 'RMB',
            'r5_Pid' => '',
            'r6_Order' => '201505110000000093',
            'r7_Uid' => '',
            'r8_MP' => '',
            'r9_BType' => '2',
            'rp_PayDate' => '2015-5-12 0:42:22',
            'hmac' => '60c166376e629a3dac948b45f9ea6688',
        ];

        $entry = [
            'id' => '201505110000000093',
            'amount' => '0.0100',
        ];

        $newBaoFoo88 = new NewBaoFoo88();
        $newBaoFoo88->setPrivateKey('QJ8vydVo340NAWk3r5tf3sTuuj0Mzm26');
        $newBaoFoo88->setOptions($options);
        $newBaoFoo88->verifyOrderPayment($entry);
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
            'pay_system' => '100025',
            'hallid' => '6',
            'p1_MerId' => '1565',
            'r0_Cmd' => 'Buy',
            'r1_Code' => '0',
            'r2_TrxId' => '201505120041006041',
            'r3_Amt' => '0.01',
            'r4_Cur' => 'RMB',
            'r5_Pid' => '',
            'r6_Order' => '201505110000000093',
            'r7_Uid' => '',
            'r8_MP' => '',
            'r9_BType' => '2',
            'rp_PayDate' => '2015-5-12 0:42:22',
            'hmac' => '76eac08600dccc9a12a43ebe912cc612',
        ];

        $entry = [
            'id' => '201505110000000093',
            'amount' => '0.0100',
        ];

        $newBaoFoo88 = new NewBaoFoo88();
        $newBaoFoo88->setPrivateKey('QJ8vydVo340NAWk3r5tf3sTuuj0Mzm26');
        $newBaoFoo88->setOptions($options);
        $newBaoFoo88->verifyOrderPayment($entry);
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
            'pay_system' => '100025',
            'hallid' => '6',
            'p1_MerId' => '1565',
            'r0_Cmd' => 'Buy',
            'r1_Code' => '1',
            'r2_TrxId' => '201505120041006041',
            'r3_Amt' => '0.01',
            'r4_Cur' => 'RMB',
            'r5_Pid' => '',
            'r6_Order' => '201505110000000093',
            'r7_Uid' => '',
            'r8_MP' => '',
            'r9_BType' => '2',
            'rp_PayDate' => '2015-5-12 0:42:22',
            'hmac' => '948b45f9ea668860c166376e629a3dac',
        ];

        $entry = [
            'id' => '20150511000000009',
            'amount' => '0.0100',
        ];

        $newBaoFoo88 = new NewBaoFoo88();
        $newBaoFoo88->setPrivateKey('QJ8vydVo340NAWk3r5tf3sTuuj0Mzm26');
        $newBaoFoo88->setOptions($options);
        $newBaoFoo88->verifyOrderPayment($entry);
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
            'pay_system' => '100025',
            'hallid' => '6',
            'p1_MerId' => '1565',
            'r0_Cmd' => 'Buy',
            'r1_Code' => '1',
            'r2_TrxId' => '201505120041006041',
            'r3_Amt' => '0.01',
            'r4_Cur' => 'RMB',
            'r5_Pid' => '',
            'r6_Order' => '201505110000000093',
            'r7_Uid' => '',
            'r8_MP' => '',
            'r9_BType' => '2',
            'rp_PayDate' => '2015-5-12 0:42:22',
            'hmac' => '948b45f9ea668860c166376e629a3dac',
        ];

        $entry = [
            'id' => '201505110000000093',
            'amount' => '30.0100',
        ];

        $newBaoFoo88 = new NewBaoFoo88();
        $newBaoFoo88->setPrivateKey('QJ8vydVo340NAWk3r5tf3sTuuj0Mzm26');
        $newBaoFoo88->setOptions($options);
        $newBaoFoo88->verifyOrderPayment($entry);
    }

    /**
     * 測試返回正常
     */
    public function testReturn()
    {
        $options = [
            'pay_system' => '100025',
            'hallid' => '6',
            'p1_MerId' => '1565',
            'r0_Cmd' => 'Buy',
            'r1_Code' => '1',
            'r2_TrxId' => '201505120041006041',
            'r3_Amt' => '0.01',
            'r4_Cur' => 'RMB',
            'r5_Pid' => '',
            'r6_Order' => '201505110000000093',
            'r7_Uid' => '',
            'r8_MP' => '',
            'r9_BType' => '2',
            'rp_PayDate' => '2015-5-12 0:42:22',
            'hmac' => '948b45f9ea668860c166376e629a3dac',
        ];

        $entry = [
            'id' => '201505110000000093',
            'amount' => '0.0100',
        ];

        $newBaoFoo88 = new NewBaoFoo88();
        $newBaoFoo88->setPrivateKey('QJ8vydVo340NAWk3r5tf3sTuuj0Mzm26');
        $newBaoFoo88->setOptions($options);
        $newBaoFoo88->verifyOrderPayment($entry);
    }
}
