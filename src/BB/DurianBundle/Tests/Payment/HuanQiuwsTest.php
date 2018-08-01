<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\HuanQiuws;

class HuanQiuwsTest extends DurianTestCase
{
    /**
     * 測試支付加密時沒有帶入privateKey的情況
     */
    public function testPayWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $huanQiuws = new HuanQiuws();
        $huanQiuws->getVerifyData();
    }

    /**
     * 測試支付加密時未指定支付參數
     */
    public function testPayWithNoPayParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No pay parameter specified',
            180145
        );

        $sourceData = ['number' => ''];

        $huanQiuws = new HuanQiuws();
        $huanQiuws->setPrivateKey('1234');
        $huanQiuws->setOptions($sourceData);
        $huanQiuws->getVerifyData();
    }

    /**
     * 測試支付加密時代入支付平台不支援的銀行
     */
    public function testPayUnsupportedPaymentVendor()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'PaymentVendor is not supported by PaymentGateway',
            180066
        );

        $sourceData = [
            'number' => '1234',
            'paymentVendorId' => '999',
            'amount' => '0.01',
            'orderId' => '201801040000008413',
        ];

        $huanQiuws = new HuanQiuws();
        $huanQiuws->setPrivateKey('1234');
        $huanQiuws->setOptions($sourceData);
        $huanQiuws->getVerifyData();
    }

    /**
     * 測試支付加密
     */
    public function testPay()
    {
        $sourceData = [
            'number' => '1234',
            'paymentVendorId' => '1090',
            'amount' => '0.01',
            'orderId' => '201801040000008413',
        ];

        $huanQiuws = new HuanQiuws();
        $huanQiuws->setPrivateKey('1234');
        $huanQiuws->setOptions($sourceData);
        $encodeData = $huanQiuws->getVerifyData();

        $this->assertEquals($sourceData['number'], $encodeData['username']);
        $this->assertEquals('0.01', $encodeData['amount']);
        $this->assertEquals('WXPAY_QRCODE', $encodeData['productId']);
        $this->assertEquals($sourceData['orderId'], $encodeData['orderRemark']);
        $this->assertEquals('4c9bffb94c261faacf0bb3ab2e2db79e', $encodeData['sign']);
    }

    /**
     * 測試解密基本參數設定沒有帶入key的情況
     */
    public function testReturnWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $huanQiuws = new HuanQiuws();
        $huanQiuws->verifyOrderPayment([]);
    }

    /**
     * 測試解密驗證支付平台未指定返回參數
     */
    public function testReturnWithNoReturnParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $sourceData = [
            'amount' => '300',
            'trade_no' => '201801031131562176903920',
        ];

        $huanQiuws = new HuanQiuws();
        $huanQiuws->setPrivateKey('1234');
        $huanQiuws->setOptions($sourceData);
        $huanQiuws->verifyOrderPayment([]);
    }

    /**
     * 測試解密驗證支付平台缺少回傳sign(加密簽名)
     */
    public function testReturnWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $sourceData = [
            'amount' => '0.01',
            'code' => '0000',
            'message' => '支付成功',
            'orderNumber' => '1515038864093wx-18123',
            'orderRemark' => '201801040000008413',
            'status' => 'SUCCESS',
            'username' => '18401618840',
        ];

        $huanQiuws = new HuanQiuws();
        $huanQiuws->setPrivateKey('1234');
        $huanQiuws->setOptions($sourceData);
        $huanQiuws->verifyOrderPayment([]);
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
            'amount' => '0.01',
            'code' => '0000',
            'message' => '支付成功',
            'orderNumber' => '1515038864093wx-18123',
            'orderRemark' => '201801040000008413',
            'status' => 'SUCCESS',
            'username' => '18401618840',
            'sign' => '45ee2b3320c8b2291dcd7c9a71991fce',
        ];

        $huanQiuws = new HuanQiuws();
        $huanQiuws->setPrivateKey('1234');
        $huanQiuws->setOptions($sourceData);
        $huanQiuws->verifyOrderPayment([]);
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
            'amount' => '0.01',
            'code' => '0000',
            'message' => '支付失敗',
            'orderNumber' => '1515038864093wx-18123',
            'orderRemark' => '201801040000008413',
            'status' => 'FAIL',
            'username' => '18401618840',
            'sign' => 'e33bdcd77ea863add654d355225b3c7c',
        ];

        $huanQiuws = new HuanQiuws();
        $huanQiuws->setPrivateKey('1234');
        $huanQiuws->setOptions($sourceData);
        $huanQiuws->verifyOrderPayment([]);
    }

    /**
     * 測試線上支付單號不正確的情況
     */
    public function testReturnOrderIdError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $sourceData = [
            'amount' => '0.01',
            'code' => '0000',
            'message' => '支付成功',
            'orderNumber' => '1515038864093wx-18123',
            'orderRemark' => '201801040000008413',
            'status' => 'SUCCESS',
            'username' => '18401618840',
            'sign' => 'f16471c0ca94465b6f5aa54174769239',
        ];

        $entry = ['id' => '201606220000002806'];

        $huanQiuws = new HuanQiuws();
        $huanQiuws->setPrivateKey('1234');
        $huanQiuws->setOptions($sourceData);
        $huanQiuws->verifyOrderPayment($entry);
    }

    /**
     * 測試線上支付金額不正確的情況
     */
    public function testReturnOrderAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $sourceData = [
            'amount' => '0.01',
            'code' => '0000',
            'message' => '支付成功',
            'orderNumber' => '1515038864093wx-18123',
            'orderRemark' => '201801040000008413',
            'status' => 'SUCCESS',
            'username' => '18401618840',
            'sign' => 'f16471c0ca94465b6f5aa54174769239',
        ];

        $entry = [
            'id' => '201801040000008413',
            'amount' => '1.0000',
        ];

        $huanQiuws = new HuanQiuws();
        $huanQiuws->setPrivateKey('1234');
        $huanQiuws->setOptions($sourceData);
        $huanQiuws->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testReturnSuccess()
    {
        $sourceData = [
            'amount' => '0.01',
            'code' => '0000',
            'message' => '支付成功',
            'orderNumber' => '1515038864093wx-18123',
            'orderRemark' => '201801040000008413',
            'status' => 'SUCCESS',
            'username' => '18401618840',
            'sign' => 'f16471c0ca94465b6f5aa54174769239',
        ];

        $entry = [
            'id' => '201801040000008413',
            'amount' => '0.01',
        ];

        $huanQiuws = new HuanQiuws();
        $huanQiuws->setPrivateKey('1234');
        $huanQiuws->setOptions($sourceData);
        $huanQiuws->verifyOrderPayment($entry);

        $this->assertEquals('SUCCESS', $huanQiuws->getMsg());
    }
}
