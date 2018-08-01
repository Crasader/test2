<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\YunXunPay;

class YunXunPayTest extends DurianTestCase
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

        $yunXunPay = new YunXunPay();
        $yunXunPay->getVerifyData();
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

        $yunXunPay = new YunXunPay();
        $yunXunPay->setPrivateKey('test');
        $yunXunPay->setOptions($sourceData);
        $yunXunPay->getVerifyData();
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
            'number' => '102400001',
            'paymentVendorId' => '999',
            'amount' => '2.00',
            'orderId' => '201708280000004052',
            'notify_url' => 'http://two123.comxa.com/',
        ];

        $yunXunPay = new YunXunPay();
        $yunXunPay->setPrivateKey('test');
        $yunXunPay->setOptions($sourceData);
        $yunXunPay->getVerifyData();
    }

    /**
     * 測試支付時postUrl為空
     */
    public function testPayWithoutPostUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No pay parameter specified',
            180145
        );

        $sourceData = [
            'number' => '102400001',
            'paymentVendorId' => '1090',
            'amount' => '2.00',
            'orderId' => '201708280000004052',
            'notify_url' => 'http://two123.comxa.com/',
            'postUrl' => '',
        ];

        $yunXunPay = new YunXunPay();
        $yunXunPay->setPrivateKey('test');
        $yunXunPay->setOptions($sourceData);
        $yunXunPay->getVerifyData();
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $sourceData = [
            'number' => '102400001',
            'paymentVendorId' => '1090',
            'amount' => '2.00',
            'orderId' => '201708280000004052',
            'notify_url' => 'http://two123.comxa.com/',
            'postUrl' => 'http://pay.6bpay.com/PayBank.aspx',
        ];

        $yunXunPay = new YunXunPay();
        $yunXunPay->setPrivateKey('test');
        $yunXunPay->setOptions($sourceData);
        $encodeData = $yunXunPay->getVerifyData();

        $actUrl = 'http://pay.6bpay.com/PayBank.aspx?partner=102400001&PayMethod=0001&paymoney=2.00&ordernumber=2' .
            '01708280000004052&callbackurl=http%3A%2F%2Ftwo123.comxa.com%2F&hrefbackurl=&attach=&iscodelink=0&sig' .
            'n=1e9e550bda9bf591a8668cc76cf6ba02';

        $this->assertEquals($sourceData['number'], $encodeData['partner']);
        $this->assertEquals('0001', $encodeData['PayMethod']);
        $this->assertSame($sourceData['amount'], $encodeData['paymoney']);
        $this->assertEquals($sourceData['orderId'], $encodeData['ordernumber']);
        $this->assertEquals($sourceData['notify_url'], $encodeData['callbackurl']);
        $this->assertEquals('', $encodeData['hrefbackurl']);
        $this->assertEquals('', $encodeData['attach']);
        $this->assertEquals('0', $encodeData['iscodelink']);
        $this->assertEquals('1e9e550bda9bf591a8668cc76cf6ba02', $encodeData['sign']);
        $this->assertEquals($actUrl, $encodeData['act_url']);
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

        $yunXunPay = new YunXunPay();
        $yunXunPay->verifyOrderPayment([]);
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

        $yunXunPay = new YunXunPay();
        $yunXunPay->setPrivateKey('test');
        $yunXunPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時支付平台缺少回傳sign
     */
    public function testReturnWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $sourceData = [
            'partner' => '102400001',
            'ordernumber' => '201703090000001810',
            'orderstatus' => '1',
            'paymoney' => '2.0000',
            'sysnumber' => 'AK102400001170321164718232',
            'attach' => '',
        ];

        $yunXunPay = new YunXunPay();
        $yunXunPay->setOptions($sourceData);
        $yunXunPay->setPrivateKey('test');
        $yunXunPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時sign簽名驗證錯誤
     */
    public function testReturnSignatureVerificationFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Signature verification failed',
            180034
        );

        $sourceData = [
            'partner' => '102400001',
            'ordernumber' => '201703090000001810',
            'orderstatus' => '1',
            'paymoney' => '2.0000',
            'sysnumber' => 'AK102400001170321164718232',
            'attach' => '',
            'sign' => 'cec460574962122c03973b04609b3cf5',
        ];

        $yunXunPay = new YunXunPay();
        $yunXunPay->setOptions($sourceData);
        $yunXunPay->setPrivateKey('test');
        $yunXunPay->verifyOrderPayment([]);
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
            'partner' => '102400001',
            'ordernumber' => '201703090000001810',
            'orderstatus' => '0',
            'paymoney' => '2.0000',
            'sysnumber' => 'AK102400001170321164718232',
            'attach' => '',
            'sign' => '8a656f711fa4d47b3c090bb48b89c3de',
        ];

        $yunXunPay = new YunXunPay();
        $yunXunPay->setOptions($sourceData);
        $yunXunPay->setPrivateKey('test');
        $yunXunPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回結果為訂單編號錯誤
     */
    public function testReturnOrderIdError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $sourceData = [
            'partner' => '102400001',
            'ordernumber' => '201703090000001810',
            'orderstatus' => '1',
            'paymoney' => '2.0000',
            'sysnumber' => 'AK102400001170321164718232',
            'attach' => '',
            'sign' => '83fef701214cf08b782f06c5facaad33',
        ];

        $entry = ['id' => '201703090000001811'];

        $yunXunPay = new YunXunPay();
        $yunXunPay->setOptions($sourceData);
        $yunXunPay->setPrivateKey('test');
        $yunXunPay->verifyOrderPayment($entry);
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
            'partner' => '102400001',
            'ordernumber' => '201703090000001810',
            'orderstatus' => '1',
            'paymoney' => '2.0000',
            'sysnumber' => 'AK102400001170321164718232',
            'attach' => '',
            'sign' => '83fef701214cf08b782f06c5facaad33',
        ];

        $entry = [
            'id' => '201703090000001810',
            'amount' => '0.01',
        ];

        $yunXunPay = new YunXunPay();
        $yunXunPay->setOptions($sourceData);
        $yunXunPay->setPrivateKey('test');
        $yunXunPay->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testPaySuccess()
    {
        $sourceData = [
            'partner' => '102400001',
            'ordernumber' => '201703090000001810',
            'orderstatus' => '1',
            'paymoney' => '2.0000',
            'sysnumber' => 'AK102400001170321164718232',
            'attach' => '',
            'sign' => '83fef701214cf08b782f06c5facaad33',
        ];

        $entry = [
            'id' => '201703090000001810',
            'amount' => '2',
        ];

        $yunXunPay = new YunXunPay();
        $yunXunPay->setOptions($sourceData);
        $yunXunPay->setPrivateKey('test');
        $yunXunPay->verifyOrderPayment($entry);

        $this->assertEquals('ok', $yunXunPay->getMsg());
    }
}
