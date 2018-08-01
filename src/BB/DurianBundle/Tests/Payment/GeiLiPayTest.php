<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\GeiLiPay;

class GeiLiPayTest extends DurianTestCase
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

        $geiLiPay = new GeiLiPay();
        $geiLiPay->getVerifyData();
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

        $geiLiPay = new GeiLiPay();
        $geiLiPay->setPrivateKey('1234');
        $geiLiPay->setOptions($sourceData);
        $geiLiPay->getVerifyData();
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
            'orderId' => '201801030000008391',
            'notify_url' => 'http://two123.comxa.com/',
        ];

        $geiLiPay = new GeiLiPay();
        $geiLiPay->setPrivateKey('1234');
        $geiLiPay->setOptions($sourceData);
        $geiLiPay->getVerifyData();
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
            'orderId' => '201801030000008391',
            'notify_url' => 'http://two123.comxa.com/',
        ];

        $geiLiPay = new GeiLiPay();
        $geiLiPay->setPrivateKey('1234');
        $geiLiPay->setOptions($sourceData);
        $encodeData = $geiLiPay->getVerifyData();

        $this->assertEquals($sourceData['number'], $encodeData['app_id']);
        $this->assertEquals('wechat', $encodeData['pay_type']);
        $this->assertEquals('1', $encodeData['amount']);
        $this->assertEquals($sourceData['orderId'], $encodeData['order_id']);
        $this->assertEquals($sourceData['notify_url'], $encodeData['notify_url']);
        $this->assertEquals($sourceData['notify_url'], $encodeData['return_url']);
        $this->assertEquals('', $encodeData['extend']);
        $this->assertEquals('1bb357c08c59d23632f83c67be2d3335', $encodeData['sign']);
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

        $geiLiPay = new GeiLiPay();
        $geiLiPay->verifyOrderPayment([]);
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

        $geiLiPay = new GeiLiPay();
        $geiLiPay->setPrivateKey('1234');
        $geiLiPay->setOptions($sourceData);
        $geiLiPay->verifyOrderPayment([]);
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
            'amount' => '300',
            'trade_no' => '201801031131562176903920',
            'order_id' => '201801030000008391',
            'app_id' => 'A201712301246251635527996',
            'state' => '1',
            'extend' => '',
        ];

        $geiLiPay = new GeiLiPay();
        $geiLiPay->setPrivateKey('1234');
        $geiLiPay->setOptions($sourceData);
        $geiLiPay->verifyOrderPayment([]);
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
            'amount' => '300',
            'trade_no' => '201801031131562176903920',
            'order_id' => '201801030000008391',
            'app_id' => 'A201712301246251635527996',
            'state' => '1',
            'extend' => '',
            'sign' => '077b947d5bd55aff624d93debc4a3f56',
        ];

        $geiLiPay = new GeiLiPay();
        $geiLiPay->setPrivateKey('1234');
        $geiLiPay->setOptions($sourceData);
        $geiLiPay->verifyOrderPayment([]);
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
            'amount' => '300',
            'trade_no' => '201801031131562176903920',
            'order_id' => '201801030000008391',
            'app_id' => 'A201712301246251635527996',
            'state' => '5',
            'extend' => '',
            'sign' => '9e78f70d06c0e8ea055582da87850e58',
        ];

        $geiLiPay = new GeiLiPay();
        $geiLiPay->setPrivateKey('1234');
        $geiLiPay->setOptions($sourceData);
        $geiLiPay->verifyOrderPayment([]);
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
            'amount' => '300',
            'trade_no' => '201801031131562176903920',
            'order_id' => '201801030000008391',
            'app_id' => 'A201712301246251635527996',
            'state' => '1',
            'extend' => '',
            'sign' => 'f0b96a376d35731550257d7cbef3905f',
        ];

        $entry = ['id' => '201606220000002806'];

        $geiLiPay = new GeiLiPay();
        $geiLiPay->setPrivateKey('1234');
        $geiLiPay->setOptions($sourceData);
        $geiLiPay->verifyOrderPayment($entry);
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
            'amount' => '300',
            'trade_no' => '201801031131562176903920',
            'order_id' => '201801030000008391',
            'app_id' => 'A201712301246251635527996',
            'state' => '1',
            'extend' => '',
            'sign' => 'f0b96a376d35731550257d7cbef3905f',
        ];

        $entry = [
            'id' => '201801030000008391',
            'amount' => '1.0000',
        ];

        $geiLiPay = new GeiLiPay();
        $geiLiPay->setPrivateKey('1234');
        $geiLiPay->setOptions($sourceData);
        $geiLiPay->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testReturnSuccess()
    {
        $sourceData = [
            'amount' => '300',
            'trade_no' => '201801031131562176903920',
            'order_id' => '201801030000008391',
            'app_id' => 'A201712301246251635527996',
            'state' => '1',
            'extend' => '',
            'sign' => 'f0b96a376d35731550257d7cbef3905f',
        ];

        $entry = [
            'id' => '201801030000008391',
            'amount' => '3',
        ];

        $geiLiPay = new GeiLiPay();
        $geiLiPay->setPrivateKey('1234');
        $geiLiPay->setOptions($sourceData);
        $geiLiPay->verifyOrderPayment($entry);

        $this->assertEquals('SUCCESS', $geiLiPay->getMsg());
    }
}
