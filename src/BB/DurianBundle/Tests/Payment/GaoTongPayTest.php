<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\GaoTongPay;

class GaoTongPayTest extends DurianTestCase
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

        $gaoTongPay = new GaoTongPay();
        $gaoTongPay->getVerifyData();
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

        $gaoTongPay = new GaoTongPay();
        $gaoTongPay->setPrivateKey('test');
        $gaoTongPay->getVerifyData();
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

        $sourceData = [
            'number' => '9527',
            'notify_url' => 'http://PoWei.HandSome.com/',
            'paymentVendorId' => '999',
            'orderId' => '201708220000009527',
            'amount' => '0.01',
        ];

        $gaoTongPay = new GaoTongPay();
        $gaoTongPay->setPrivateKey('test');
        $gaoTongPay->setOptions($sourceData);
        $gaoTongPay->getVerifyData();
    }

    /**
     * 測試支付沒有帶入postUrl的情況
     */
    public function testPayWithoutPostUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No pay parameter specified',
            180145
        );

        $sourceData = [
            'number' => '9527',
            'paymentVendorId' => '1',
            'amount' => '0.01',
            'orderId' => '201708220000009527',
            'notify_url' => 'http://PoWei.HandSome.com/',
            'postUrl' => '',
        ];

        $gaoTongPay = new GaoTongPay();
        $gaoTongPay->setPrivateKey('test');
        $gaoTongPay->setOptions($sourceData);
        $gaoTongPay->getVerifyData();
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $sourceData = [
            'number' => '9527',
            'paymentVendorId' => '1',
            'amount' => '1.00',
            'orderId' => '201708220000009527',
            'notify_url' => 'http://PoWei.HandSome.com/',
            'postUrl' => 'http://wgtj.gaotongpay.com/PayBank.aspx',
        ];

        $gaoTongPay = new GaoTongPay();
        $gaoTongPay->setPrivateKey('test');
        $gaoTongPay->setOptions($sourceData);
        $encodeData = $gaoTongPay->getVerifyData();

        $postUrl = 'http://wgtj.gaotongpay.com/PayBank.aspx?partner=9527&banktype=ICBC&paymoney=1.00' .
            '&ordernumber=201708220000009527&callbackurl=http%3A%2F%2FPoWei.HandSome.com%2F&' .
            'hrefbackurl=&attach=&sign=c56124cc36dd1329e741ce16ceb4ed0e';

        $this->assertEquals($sourceData['number'], $encodeData['partner']);
        $this->assertEquals('ICBC', $encodeData['banktype']);
        $this->assertEquals($sourceData['amount'], $encodeData['paymoney']);
        $this->assertEquals($sourceData['orderId'], $encodeData['ordernumber']);
        $this->assertEquals($sourceData['notify_url'], $encodeData['callbackurl']);
        $this->assertEquals('', $encodeData['hrefbackurl']);
        $this->assertEquals('', $encodeData['attach']);
        $this->assertEquals('c56124cc36dd1329e741ce16ceb4ed0e', $encodeData['sign']);
        $this->assertEquals($postUrl, $encodeData['act_url']);
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

        $gaoTongPay = new GaoTongPay();
        $gaoTongPay->verifyOrderPayment([]);
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

        $gaoTongPay = new GaoTongPay();
        $gaoTongPay->setPrivateKey('test');
        $gaoTongPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時缺少sign
     */
    public function testReturnWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $sourceData = [
            'partner' => '9527',
            'ordernumber' => '201708220000009527',
            'orderstatus' => '1',
            'paymoney' => '0.10',
            'sysnumber' => 'J1697216092913397188210000',
            'attach' => '',
        ];

        $gaoTongPay = new GaoTongPay();
        $gaoTongPay->setPrivateKey('test');
        $gaoTongPay->setOptions($sourceData);
        $gaoTongPay->verifyOrderPayment([]);
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
            'partner' => '9527',
            'ordernumber' => '201708220000009527',
            'orderstatus' => '1',
            'paymoney' => '0.10',
            'sysnumber' => 'J1697216092913397188210000',
            'attach' => '',
            'sign' => '6aed90cc1da387bf5443123',
        ];

        $gaoTongPay = new GaoTongPay();
        $gaoTongPay->setPrivateKey('test');
        $gaoTongPay->setOptions($sourceData);
        $gaoTongPay->verifyOrderPayment([]);
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
            'partner' => '9527',
            'ordernumber' => '201708220000009527',
            'orderstatus' => '999',
            'paymoney' => '0.10',
            'sysnumber' => 'J1697216092913397188210000',
            'attach' => '',
            'sign' => '6b95d2ee4c1b84a267f63134cb573b96',
        ];

        $gaoTongPay = new GaoTongPay();
        $gaoTongPay->setPrivateKey('test');
        $gaoTongPay->setOptions($sourceData);
        $gaoTongPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回結果為訂單單號錯誤
     */
    public function testReturnOrderIdFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $sourceData = [
            'partner' => '9527',
            'ordernumber' => '201708220000009527',
            'orderstatus' => '1',
            'paymoney' => '0.10',
            'sysnumber' => 'J1697216092913397188210000',
            'attach' => '',
            'sign' => 'f425b33599b134b3b5a08561757d30a0',
        ];

        $entry = ['id' => '201609290000004499'];

        $gaoTongPay = new GaoTongPay();
        $gaoTongPay->setPrivateKey('test');
        $gaoTongPay->setOptions($sourceData);
        $gaoTongPay->verifyOrderPayment($entry);
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

        $sourceData = [
            'partner' => '9527',
            'ordernumber' => '201708220000009527',
            'orderstatus' => '1',
            'paymoney' => '0.10',
            'sysnumber' => 'J1697216092913397188210000',
            'attach' => '',
            'sign' => 'f425b33599b134b3b5a08561757d30a0',
        ];

        $entry = [
            'id' => '201708220000009527',
            'amount' => '15.00',
        ];

        $gaoTongPay = new GaoTongPay();
        $gaoTongPay->setPrivateKey('test');
        $gaoTongPay->setOptions($sourceData);
        $gaoTongPay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果
     */
    public function testReturnOrder()
    {
        $sourceData = [
            'partner' => '9527',
            'ordernumber' => '201708220000009527',
            'orderstatus' => '1',
            'paymoney' => '0.10',
            'sysnumber' => 'J1697216092913397188210000',
            'attach' => '',
            'sign' => 'f425b33599b134b3b5a08561757d30a0',
        ];

        $entry = [
            'id' => '201708220000009527',
            'amount' => '0.1',
        ];

        $gaoTongPay = new GaoTongPay();
        $gaoTongPay->setPrivateKey('test');
        $gaoTongPay->setOptions($sourceData);
        $gaoTongPay->verifyOrderPayment($entry);

        $this->assertEquals('ok', $gaoTongPay->getMsg());
    }
}
