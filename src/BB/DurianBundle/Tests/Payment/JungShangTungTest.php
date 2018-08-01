<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\JungShangTung;

class JungShangTungTest extends DurianTestCase
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

        $jungShangTung = new JungShangTung();
        $jungShangTung->getVerifyData();
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

        $jungShangTung = new JungShangTung();
        $jungShangTung->setPrivateKey('test');
        $jungShangTung->getVerifyData();
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
            'number' => '1365',
            'orderId' => '201801220000003839',
            'amount' => '0.01',
            'notify_url' => 'http://pay.my/pay/return.php',
            'paymentVendorId' => '999',
        ];

        $jungShangTung = new JungShangTung();
        $jungShangTung->setPrivateKey('test');
        $jungShangTung->setOptions($options);
        $jungShangTung->getVerifyData();
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $options = [
            'number' => '1365',
            'orderId' => '201801220000003839',
            'amount' => '0.01',
            'notify_url' => 'http://pay.my/pay/return.php',
            'paymentVendorId' => '1',
        ];

        $jungShangTung = new JungShangTung();
        $jungShangTung->setPrivateKey('test');
        $jungShangTung->setOptions($options);
        $requestData = $jungShangTung->getVerifyData();

        $this->assertEquals('1365', $requestData['userid']);
        $this->assertEquals('201801220000003839', $requestData['orderid']);
        $this->assertEquals('0.01', $requestData['money']);
        $this->assertEquals('http://pay.my/pay/return.php', $requestData['url']);
        $this->assertEquals('', $requestData['aurl']);
        $this->assertEquals('1002', $requestData['bankid']);
        $this->assertEquals('fb390fcb4e935d431a0bc139cbfae787', $requestData['sign']);
        $this->assertEquals('', $requestData['ext']);
        $this->assertEquals('a36c690522062a81d1bbc8b9cd0d6124', $requestData['sign2']);
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

        $jungShangTung = new JungShangTung();
        $jungShangTung->verifyOrderPayment([]);
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

        $jungShangTung = new JungShangTung();
        $jungShangTung->setPrivateKey('test');
        $jungShangTung->verifyOrderPayment([]);
    }

    /**
     * 測試返回時缺少加密簽名sign
     */
    public function testReturnWithoutSignMsg()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $options = [
            'returncode' => '1',
            'userid' => '1365',
            'orderid' => '201801220000003839',
            'money' => '10.00',
            'sign2' => '875b203bde53bf3f67e1e3eecd4bade0',
            'ext' => '',
        ];

        $jungShangTung = new JungShangTung();
        $jungShangTung->setPrivateKey('test');
        $jungShangTung->setOptions($options);
        $jungShangTung->verifyOrderPayment([]);
    }

    /**
     * 測試返回時缺少加密簽名sign2
     */
    public function testReturnWithoutSign2Msg()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $options = [
            'returncode' => '1',
            'userid' => '1365',
            'orderid' => '201801220000003839',
            'money' => '10.00',
            'sign' => '2e0a71e40f3fa0ebb47cddac6ad8af18',
            'ext' => '',
        ];

        $jungShangTung = new JungShangTung();
        $jungShangTung->setPrivateKey('test');
        $jungShangTung->setOptions($options);
        $jungShangTung->verifyOrderPayment([]);
    }

    /**
     * 測試返回時簽名驗證sign錯誤
     */
    public function testReturnSignatureVerificationFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Signature verification failed',
            180034
        );

        $options = [
            'returncode' => '1',
            'userid' => '1365',
            'orderid' => '201801220000003839',
            'money' => '10.00',
            'sign' => '2e0a71e40f3fa0ebb47cddac6ad8af18',
            'sign2' => '875b203bde53bf3f67e1e3eecd4bade0',
            'ext' => '',
        ];

        $jungShangTung = new JungShangTung();
        $jungShangTung->setPrivateKey('test');
        $jungShangTung->setOptions($options);
        $jungShangTung->verifyOrderPayment([]);
    }

    /**
     * 測試返回時簽名驗證sign2錯誤
     */
    public function testReturnSignature2VerificationFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Signature verification failed',
            180034
        );

        $options = [
            'returncode' => '1',
            'userid' => '1365',
            'orderid' => '201801220000003839',
            'money' => '10.00',
            'sign' => '62fe782c8ea047de4fa8e5b371d976d3',
            'sign2' => '875b203bde53bf3f67e1e3eecd4bade0',
            'ext' => '',
        ];

        $jungShangTung = new JungShangTung();
        $jungShangTung->setPrivateKey('test');
        $jungShangTung->setOptions($options);
        $jungShangTung->verifyOrderPayment([]);
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
            'returncode' => '0',
            'userid' => '1365',
            'orderid' => '201801220000003839',
            'money' => '10.00',
            'sign' => 'd97c39ce8b83c3d425d33213c28dd267',
            'sign2' => '68477b0cb2489c03850f3e394cadc421',
            'ext' => '',
        ];

        $jungShangTung = new JungShangTung();
        $jungShangTung->setPrivateKey('test');
        $jungShangTung->setOptions($options);
        $jungShangTung->verifyOrderPayment([]);
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
            'returncode' => '1',
            'userid' => '1365',
            'orderid' => '201801220000003839',
            'money' => '10.00',
            'sign' => '62fe782c8ea047de4fa8e5b371d976d3',
            'sign2' => '9d4106412e948326bb5053cb95e6812e',
            'ext' => '',
        ];

        $entry = [
            'id' => '201801220000003838',
            'amount' => '10',
        ];

        $jungShangTung = new JungShangTung();
        $jungShangTung->setPrivateKey('test');
        $jungShangTung->setOptions($options);
        $jungShangTung->verifyOrderPayment($entry);
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
            'returncode' => '1',
            'userid' => '1365',
            'orderid' => '201801220000003839',
            'money' => '10.00',
            'sign' => '62fe782c8ea047de4fa8e5b371d976d3',
            'sign2' => '9d4106412e948326bb5053cb95e6812e',
            'ext' => '',
        ];

        $entry = [
            'id' => '201801220000003839',
            'amount' => '1',
        ];

        $jungShangTung = new JungShangTung();
        $jungShangTung->setPrivateKey('test');
        $jungShangTung->setOptions($options);
        $jungShangTung->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果
     */
    public function testReturnOrder()
    {
        $options = [
            'returncode' => '1',
            'userid' => '1365',
            'orderid' => '201801220000003839',
            'money' => '10.00',
            'sign' => '62fe782c8ea047de4fa8e5b371d976d3',
            'sign2' => '9d4106412e948326bb5053cb95e6812e',
            'ext' => '',
        ];

        $entry = [
            'id' => '201801220000003839',
            'amount' => '10',
        ];

        $jungShangTung = new JungShangTung();
        $jungShangTung->setPrivateKey('test');
        $jungShangTung->setOptions($options);
        $jungShangTung->verifyOrderPayment($entry);

        $this->assertEquals('ok', $jungShangTung->getMsg());
    }
}
