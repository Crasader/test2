<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\JFooPay;

class JFooPayTest extends DurianTestCase
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

        $jFooPay = new JFooPay();
        $jFooPay->getVerifyData();
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

        $jFooPay = new JFooPay();
        $jFooPay->setPrivateKey('test');
        $jFooPay->setOptions($sourceData);
        $jFooPay->getVerifyData();
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
            'number' => '17082',
            'paymentVendorId' => '999',
            'amount' => '2.00',
            'orderId' => '201708280000004048',
            'notify_url' => 'http://two123.comxa.com/',
        ];

        $jFooPay = new JFooPay();
        $jFooPay->setPrivateKey('test');
        $jFooPay->setOptions($sourceData);
        $jFooPay->getVerifyData();
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
            'number' => '17082',
            'paymentVendorId' => '1090',
            'amount' => '2.00',
            'orderId' => '201708280000004048',
            'notify_url' => 'http://two123.comxa.com/',
            'postUrl' => '',
        ];

        $jFooPay = new JFooPay();
        $jFooPay->setPrivateKey('test');
        $jFooPay->setOptions($sourceData);
        $jFooPay->getVerifyData();
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $sourceData = [
            'number' => '17082',
            'paymentVendorId' => '1090',
            'amount' => '2.00',
            'orderId' => '201708280000004048',
            'notify_url' => 'http://two123.comxa.com/',
            'postUrl' => 'http://pay.6bpay.com/PayBank.aspx',
        ];

        $jFooPay = new JFooPay();
        $jFooPay->setPrivateKey('test');
        $jFooPay->setOptions($sourceData);
        $encodeData = $jFooPay->getVerifyData();

        $actUrl = 'http://pay.6bpay.com/PayBank.aspx?partner=17082&banktype=WEIXIN&paymoney=2.00&ordernumber=201708' .
            '280000004048&callbackurl=http%3A%2F%2Ftwo123.comxa.com%2F&hrefbackurl=&attach=&sign=6e3ab8fc41b59a3ab1' .
            'c1c4db919e64f3';

        $this->assertEquals($sourceData['number'], $encodeData['partner']);
        $this->assertEquals('WEIXIN', $encodeData['banktype']);
        $this->assertSame($sourceData['amount'], $encodeData['paymoney']);
        $this->assertEquals($sourceData['orderId'], $encodeData['ordernumber']);
        $this->assertEquals($sourceData['notify_url'], $encodeData['callbackurl']);
        $this->assertEquals('', $encodeData['hrefbackurl']);
        $this->assertEquals('', $encodeData['attach']);
        $this->assertEquals('6e3ab8fc41b59a3ab1c1c4db919e64f3', $encodeData['sign']);
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

        $jFooPay = new JFooPay();
        $jFooPay->verifyOrderPayment([]);
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

        $jFooPay = new JFooPay();
        $jFooPay->setPrivateKey('test');
        $jFooPay->verifyOrderPayment([]);
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
            'partner' => '17082',
            'ordernumber' => '201703090000001810',
            'orderstatus' => '1',
            'paymoney' => '2.0000',
            'sysnumber' => 'AK17082170321164718232',
            'attach' => '',
        ];

        $jFooPay = new JFooPay();
        $jFooPay->setOptions($sourceData);
        $jFooPay->setPrivateKey('test');
        $jFooPay->verifyOrderPayment([]);
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
            'partner' => '17082',
            'ordernumber' => '201703090000001810',
            'orderstatus' => '1',
            'paymoney' => '2.0000',
            'sysnumber' => 'AK17082170321164718232',
            'attach' => '',
            'sign' => 'cec460574962122c03973b04609b3cf5',
        ];

        $jFooPay = new JFooPay();
        $jFooPay->setOptions($sourceData);
        $jFooPay->setPrivateKey('test');
        $jFooPay->verifyOrderPayment([]);
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
            'partner' => '17082',
            'ordernumber' => '201703090000001810',
            'orderstatus' => '0',
            'paymoney' => '2.0000',
            'sysnumber' => 'AK17082170321164718232',
            'attach' => '',
            'sign' => 'd9ddacae5618e135ad1887bd30279b16',
        ];

        $jFooPay = new JFooPay();
        $jFooPay->setOptions($sourceData);
        $jFooPay->setPrivateKey('test');
        $jFooPay->verifyOrderPayment([]);
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
            'partner' => '17082',
            'ordernumber' => '201703090000001810',
            'orderstatus' => '1',
            'paymoney' => '2.0000',
            'sysnumber' => 'AK17082170321164718232',
            'attach' => '',
            'sign' => '866852001b5d5bfe858ced3864716d76',
        ];

        $entry = ['id' => '201703090000001811'];

        $jFooPay = new JFooPay();
        $jFooPay->setOptions($sourceData);
        $jFooPay->setPrivateKey('test');
        $jFooPay->verifyOrderPayment($entry);
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
            'partner' => '17082',
            'ordernumber' => '201703090000001810',
            'orderstatus' => '1',
            'paymoney' => '2.0000',
            'sysnumber' => 'AK17082170321164718232',
            'attach' => '',
            'sign' => '866852001b5d5bfe858ced3864716d76',
        ];

        $entry = [
            'id' => '201703090000001810',
            'amount' => '0.01',
        ];

        $jFooPay = new JFooPay();
        $jFooPay->setOptions($sourceData);
        $jFooPay->setPrivateKey('test');
        $jFooPay->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testPaySuccess()
    {
        $sourceData = [
            'partner' => '17082',
            'ordernumber' => '201703090000001810',
            'orderstatus' => '1',
            'paymoney' => '2.0000',
            'sysnumber' => 'AK17082170321164718232',
            'attach' => '',
            'sign' => '866852001b5d5bfe858ced3864716d76',
        ];

        $entry = [
            'id' => '201703090000001810',
            'amount' => '2',
        ];

        $jFooPay = new JFooPay();
        $jFooPay->setOptions($sourceData);
        $jFooPay->setPrivateKey('test');
        $jFooPay->verifyOrderPayment($entry);

        $this->assertEquals('ok', $jFooPay->getMsg());
    }
}
