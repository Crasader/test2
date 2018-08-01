<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\HaioPay;

class HaioPayTest extends DurianTestCase
{
    /**
     * 測試支付加密時沒有帶入privateKey的情況
     */
    public function testPayEncodeWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $haioPay = new HaioPay();
        $haioPay->getVerifyData();
    }

    /**
     * 測試加密基本參數設定未指定支付參數
     */
    public function testPayWithNoPayParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No pay parameter specified',
            180145
        );

        $haioPay = new HaioPay();

        $haioPay->setPrivateKey('1234');
        $haioPay->getVerifyData();
    }

    /**
     * 測試支付加密時代入支付平台不支援的銀行
     */
    public function testPayEncodeUnsupportedPaymentVendor()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'PaymentVendor is not supported by PaymentGateway',
            180066
        );

        $sourceData = [
            'number' => '123456789',
            'orderId' => '201709150000000893',
            'amount' => '0.01',
            'paymentVendorId' => '999',
            'notify_url' => 'http://payment/return.php',
            'orderCreateDate' => '2017-09-14 10:16:02',
        ];

        $haioPay = new HaioPay();
        $haioPay->setPrivateKey('1234');
        $haioPay->setOptions($sourceData);
        $haioPay->getVerifyData();
    }

    /**
     * 測試加密
     */
    public function testGetEncodeData()
    {
        $sourceData = [
            'number' => '123456789',
            'orderId' => '201709150000000893',
            'amount' => '0.01',
            'paymentVendorId' => '1',
            'notify_url' => 'http://payment/return.php',
            'orderCreateDate' => '2017-09-14 10:16:02',
        ];

        $haioPay = new HaioPay();
        $haioPay->setPrivateKey('1234');
        $haioPay->setOptions($sourceData);
        $encodeData = $haioPay->getVerifyData();

        $this->assertEquals($sourceData['number'], $encodeData['userid']);
        $this->assertEquals($sourceData['orderId'], $encodeData['orderid']);
        $this->assertEquals('icbc', $encodeData['payvia']);
        $this->assertEquals($sourceData['amount'], $encodeData['price']);
        $this->assertEquals('20170914101602', $encodeData['timespan']);
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

        $haioPay = new HaioPay();

        $haioPay->verifyOrderPayment([]);
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

        $haioPay = new HaioPay();
        $haioPay->setPrivateKey('1234');
        $haioPay->verifyOrderPayment([]);
    }

    /**
     * 測試解密驗證時支付平台缺少回傳sign參數
     */
    public function testReturnWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $sourceData = [
            'userid' => '123456789',
            'orderid' => '201709150000000893',
            'billno' => '20170915103908696478U',
            'price' => '1.01',
            'payvia' => 'ICBC-NET',
            'state' => '1',
            'ext' => '',
            'custom' => '',
            'timespan' => '20170915104534',
        ];

        $haioPay = new HaioPay();
        $haioPay->setPrivateKey('1234');
        $haioPay->setOptions($sourceData);
        $haioPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時簽名錯誤
     */
    public function testReturnSignatureVerificationFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Signature verification failed',
            180034
        );

        $sourceData = [
            'userid' => '123456789',
            'orderid' => '201709150000000893',
            'billno' => '20170915103908696478U',
            'price' => '1.01',
            'payvia' => 'ICBC-NET',
            'state' => '1',
            'ext' => '',
            'custom' => '',
            'timespan' => '20170915104534',
            'sign' => '1579897a8dbd5ad7e364b184de99a18e',
        ];

        $haioPay = new HaioPay();
        $haioPay->setPrivateKey('12345');
        $haioPay->setOptions($sourceData);
        $haioPay->verifyOrderPayment([]);
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
            'userid' => '123456789',
            'orderid' => '201709150000000893',
            'billno' => '20170915103908696478U',
            'price' => '1.01',
            'payvia' => 'ICBC-NET',
            'state' => '2',
            'ext' => '',
            'custom' => '',
            'timespan' => '20170915104534',
            'sign' => '2235a8a60e31409c93f142f6027b32ed',
        ];

        $haioPay = new HaioPay();
        $haioPay->setPrivateKey('1234');
        $haioPay->setOptions($sourceData);
        $haioPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回訂單號錯誤
     */
    public function testOrderIdError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $sourceData = [
            'userid' => '123456789',
            'orderid' => '201709150000000893',
            'billno' => '20170915103908696478U',
            'price' => '1.01',
            'payvia' => 'ICBC-NET',
            'state' => '1',
            'ext' => '',
            'custom' => '',
            'timespan' => '20170915104534',
            'sign' => 'e73a4372e0379e830426f0114ccc9d79',
        ];

        $entry = [
            'id' => '201709150000000894',
            'amount' => '1.01',
        ];

        $haioPay = new HaioPay();
        $haioPay->setPrivateKey('1234');
        $haioPay->setOptions($sourceData);
        $haioPay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回金額錯誤
     */
    public function testOrderAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $sourceData = [
            'userid' => '123456789',
            'orderid' => '201709150000000893',
            'billno' => '20170915103908696478U',
            'price' => '1.01',
            'payvia' => 'ICBC-NET',
            'state' => '1',
            'ext' => '',
            'custom' => '',
            'timespan' => '20170915104534',
            'sign' => 'e73a4372e0379e830426f0114ccc9d79',
        ];

        $entry = [
            'id' => '201709150000000893',
            'amount' => '2.01',
        ];

        $haioPay = new HaioPay();
        $haioPay->setPrivateKey('1234');
        $haioPay->setOptions($sourceData);
        $haioPay->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testReturnSuccess()
    {
        $sourceData = [
            'userid' => '123456789',
            'orderid' => '201709150000000893',
            'billno' => '20170915103908696478U',
            'price' => '1.01',
            'payvia' => 'ICBC-NET',
            'state' => '1',
            'ext' => '',
            'custom' => '',
            'timespan' => '20170915104534',
            'sign' => 'e73a4372e0379e830426f0114ccc9d79',
        ];

        $entry = [
            'id' => '201709150000000893',
            'amount' => '1.01',
        ];

        $haioPay = new HaioPay();
        $haioPay->setPrivateKey('1234');
        $haioPay->setOptions($sourceData);
        $haioPay->verifyOrderPayment($entry);

        $this->assertEquals('success', $haioPay->getMsg());
    }
}