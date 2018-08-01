<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\BaoBao;

class BaoBaoTest extends DurianTestCase
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

        $baoBao = new BaoBao();
        $baoBao->getVerifyData();
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

        $baoBao = new BaoBao();
        $baoBao->setPrivateKey('1234');
        $baoBao->setOptions($sourceData);
        $baoBao->getVerifyData();
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
            'orderId' => '201801260000008688',
            'notify_url' => 'http://two123.comuv.com',
            'username' => 'php1test',
            'orderCreateDate' => '2018-01-26 12:45:00',
        ];

        $baoBao = new BaoBao();
        $baoBao->setPrivateKey('1234');
        $baoBao->setOptions($sourceData);
        $baoBao->getVerifyData();
    }

    /**
     * 測試支付加密
     */
    public function testPay()
    {
        $sourceData = [
            'number' => '1234',
            'paymentVendorId' => '1',
            'amount' => '0.01',
            'orderId' => '201801260000008688',
            'notify_url' => 'http://two123.comuv.com',
            'username' => 'php1test',
            'orderCreateDate' => '2018-01-26 12:45:00',
        ];

        $baoBao = new BaoBao();
        $baoBao->setPrivateKey('1234');
        $baoBao->setOptions($sourceData);
        $encodeData = $baoBao->getVerifyData();

        $this->assertEquals($sourceData['number'], $encodeData['merchant_id']);
        $this->assertEquals($sourceData['orderId'], $encodeData['billno']);
        $this->assertEquals($sourceData['amount'], $encodeData['amount']);
        $this->assertEquals('20180126124500', $encodeData['order_date']);
        $this->assertEquals($sourceData['notify_url'], $encodeData['notify_url']);
        $this->assertEquals('ICBC', $encodeData['bank_code']);
        $this->assertEquals('php1test', $encodeData['goods_name']);
        $this->assertEquals('1', $encodeData['pay_type']);
        $this->assertEquals('MD5', $encodeData['sign_type']);
        $this->assertEquals('07e3fcb8313d9d79461c365361d52c66', $encodeData['sign']);
    }

    /**
     * 測試QQ二维支付加密
     */
    public function testPayWithQQ()
    {
        $sourceData = [
            'number' => '1234',
            'paymentVendorId' => '1103',
            'amount' => '0.01',
            'orderId' => '201801260000008688',
            'notify_url' => 'http://two123.comuv.com',
            'username' => 'php1test',
            'orderCreateDate' => '2018-01-26 12:45:00',
        ];

        $baoBao = new BaoBao();
        $baoBao->setPrivateKey('1234');
        $baoBao->setOptions($sourceData);
        $encodeData = $baoBao->getVerifyData();

        $this->assertEquals($sourceData['number'], $encodeData['merchant_id']);
        $this->assertEquals($sourceData['orderId'], $encodeData['billno']);
        $this->assertEquals($sourceData['amount'], $encodeData['amount']);
        $this->assertEquals('20180126124500', $encodeData['order_date']);
        $this->assertEquals($sourceData['notify_url'], $encodeData['notify_url']);
        $this->assertEquals('QQPay', $encodeData['bank_code']);
        $this->assertEquals('php1test', $encodeData['goods_name']);
        $this->assertEquals('4', $encodeData['pay_type']);
        $this->assertEquals('MD5', $encodeData['sign_type']);
        $this->assertEquals('07e3fcb8313d9d79461c365361d52c66', $encodeData['sign']);
    }

    /**
     * 測試返回時基本參數設定沒有帶入key的情況
     */
    public function testReturnWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $baoBao = new BaoBao();
        $baoBao->verifyOrderPayment([]);
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

        $sourceData = [
            'mId' => '50009',
            'orderNumber' => '201801260000008688',
        ];

        $baoBao = new BaoBao();
        $baoBao->setPrivateKey('1234');
        $baoBao->setOptions($sourceData);
        $baoBao->verifyOrderPayment([]);
    }

    /**
     * 測試返回時支付平台缺少回傳sign(加密簽名)
     */
    public function testReturnWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $sourceData = [
            'billno' => '201801260000008688',
            'merchant_id' => '2000002146',
            'order_id' => '20180126135421956767242735980544',
            'success' => 'Success',
            'amount' => '1.000000',
            'message' => '',
            'order_date' => '20180126135417',
            'attach' => '',
            'sign_type' => 'MD5',
        ];

        $baoBao = new BaoBao();
        $baoBao->setPrivateKey('1234');
        $baoBao->setOptions($sourceData);
        $baoBao->verifyOrderPayment([]);
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
            'billno' => '201801260000008688',
            'merchant_id' => '2000002146',
            'order_id' => '20180126135421956767242735980544',
            'success' => 'Success',
            'amount' => '1.000000',
            'message' => '',
            'order_date' => '20180126135417',
            'attach' => '',
            'sign_type' => 'MD5',
            'sign' => '1582b73409700ddcbd077634efe8f9c6',
        ];

        $baoBao = new BaoBao();
        $baoBao->setPrivateKey('1234');
        $baoBao->setOptions($sourceData);
        $baoBao->verifyOrderPayment([]);
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
            'billno' => '201801260000008688',
            'merchant_id' => '2000002146',
            'order_id' => '20180126135421956767242735980544',
            'success' => 'Fail',
            'amount' => '1.000000',
            'message' => '',
            'order_date' => '20180126135417',
            'attach' => '',
            'sign_type' => 'MD5',
            'sign' => 'cdb00eb7e7db45e2cd1eaaa52b33b712',
        ];

        $baoBao = new BaoBao();
        $baoBao->setPrivateKey('1234');
        $baoBao->setOptions($sourceData);
        $baoBao->verifyOrderPayment([]);
    }

    /**
     * 測試返回支付單號不正確的情況
     */
    public function testReturnOrderIdError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $sourceData = [
            'billno' => '201801260000008688',
            'merchant_id' => '2000002146',
            'order_id' => '20180126135421956767242735980544',
            'success' => 'Success',
            'amount' => '1.000000',
            'message' => '',
            'order_date' => '20180126135417',
            'attach' => '',
            'sign_type' => 'MD5',
            'sign' => 'e82c6ddb8efe7c693027c66d939e496d',
        ];

        $entry = ['id' => '201606220000002806'];

        $baoBao = new BaoBao();
        $baoBao->setPrivateKey('1234');
        $baoBao->setOptions($sourceData);
        $baoBao->verifyOrderPayment($entry);
    }

    /**
     * 測試返回支付金額不正確的情況
     */
    public function testReturnOrderAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $sourceData = [
            'billno' => '201801260000008688',
            'merchant_id' => '2000002146',
            'order_id' => '20180126135421956767242735980544',
            'success' => 'Success',
            'amount' => '1.000000',
            'message' => '',
            'order_date' => '20180126135417',
            'attach' => '',
            'sign_type' => 'MD5',
            'sign' => 'e82c6ddb8efe7c693027c66d939e496d',
        ];

        $entry = [
            'id' => '201801260000008688',
            'amount' => '1.10',
        ];

        $baoBao = new BaoBao();
        $baoBao->setPrivateKey('1234');
        $baoBao->setOptions($sourceData);
        $baoBao->verifyOrderPayment($entry);
    }

    /**
     * 測試返回驗證成功
     */
    public function testReturnSuccess()
    {
        $sourceData = [
            'billno' => '201801260000008688',
            'merchant_id' => '2000002146',
            'order_id' => '20180126135421956767242735980544',
            'success' => 'Success',
            'amount' => '1.000000',
            'message' => '',
            'order_date' => '20180126135417',
            'attach' => '',
            'sign_type' => 'MD5',
            'sign' => 'e82c6ddb8efe7c693027c66d939e496d',
        ];

        $entry = [
            'id' => '201801260000008688',
            'amount' => '1.00',
        ];

        $baoBao = new BaoBao();
        $baoBao->setPrivateKey('1234');
        $baoBao->setOptions($sourceData);
        $baoBao->verifyOrderPayment($entry);

        $this->assertEquals('ok', $baoBao->getMsg());
    }
}
