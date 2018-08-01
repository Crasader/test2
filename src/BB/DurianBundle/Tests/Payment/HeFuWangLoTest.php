<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\HeFuWangLo;

class HeFuWangLoTest extends DurianTestCase
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

        $heFuWangLo = new HeFuWangLo();
        $heFuWangLo->getVerifyData();
    }

    /**
     * 測試支付時沒未指定支付參數
     */
    public function testPayWithNoPayParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No pay parameter specified',
            180145
        );

        $heFuWangLo = new HeFuWangLo();
        $heFuWangLo->setPrivateKey('test');
        $heFuWangLo->getVerifyData();
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
            'notify_url' => 'http://two123.comxa.com/',
            'paymentVendorId' => '100',
            'number' => '16965',
            'orderId' => '201709150000007037',
            'amount' => '100',
        ];

        $heFuWangLo = new HeFuWangLo();
        $heFuWangLo->setPrivateKey('test');
        $heFuWangLo->setOptions($options);
        $heFuWangLo->getVerifyData();
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

        $options = [
            'notify_url' => 'http://two123.comxa.com/',
            'paymentVendorId' => '1090',
            'number' => '16965',
            'orderId' => '201709150000007037',
            'amount' => '100',
            'postUrl' => '',
        ];

        $heFuWangLo = new HeFuWangLo();
        $heFuWangLo->setPrivateKey('test');
        $heFuWangLo->setOptions($options);
        $heFuWangLo->getVerifyData();
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $options = [
            'notify_url' => 'http://two123.comxa.com/',
            'paymentVendorId' => '1090',
            'number' => '16965',
            'orderId' => '201709150000007037',
            'amount' => '100',
            'postUrl' => 'http://api.andpay.info/paybank.aspx'
        ];

        $heFuWangLo = new HeFuWangLo();
        $heFuWangLo->setPrivateKey('test');
        $heFuWangLo->setOptions($options);
        $requestData = $heFuWangLo->getVerifyData();

        $this->assertEquals($options['number'], $requestData['partner']);
        $this->assertEquals($options['orderId'], $requestData['ordernumber']);
        $this->assertEquals('100.00', $requestData['paymoney']);
        $this->assertEquals($options['notify_url'], $requestData['callbackurl']);
        $this->assertEquals('WEIXIN', $requestData['banktype']);
        $this->assertEquals('726bc6f8e8528b2dce7ebe70f4d91d1e', $requestData['sign']);

        $postUrl = 'http://api.andpay.info/paybank.aspx?partner=16965&banktype=WEIXIN&' .
            'paymoney=100.00&ordernumber=201709150000007037&callbackurl=http%3A%2F%2Ftwo123.' .
            'comxa.com%2F&hrefbackurl=&attach=&sign=726bc6f8e8528b2dce7ebe70f4d91d1e&isshow=1';
        $this->assertEquals($postUrl, $requestData['act_url']);
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

        $heFuWangLo = new HeFuWangLo();
        $heFuWangLo->verifyOrderPayment([]);
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

        $heFuWangLo = new HeFuWangLo();
        $heFuWangLo->setPrivateKey('test');
        $heFuWangLo->verifyOrderPayment([]);
    }

    /**
     * 測試返回時缺少簽名
     */
    public function testReturnWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $options = [
            'partner' => '802442',
            'ordernumber' => '201802050000008889',
            'orderstatus' => '1',
            'paymoney' => '10.0000',
            'sysnumber' => '20180205164833662571000000',
            'attach' => '',
        ];

        $heFuWangLo = new HeFuWangLo();
        $heFuWangLo->setPrivateKey('test');
        $heFuWangLo->setOptions($options);
        $heFuWangLo->verifyOrderPayment([]);
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
            'partner' => '802442',
            'ordernumber' => '201802050000008889',
            'orderstatus' => '1',
            'paymoney' => '10.0000',
            'sysnumber' => '20180205164833662571000000',
            'attach' => '',
            'sign' => '74c5054f9fa72016dc23c3fb72d467fc',
        ];

        $heFuWangLo = new HeFuWangLo();
        $heFuWangLo->setPrivateKey('test');
        $heFuWangLo->setOptions($options);
        $heFuWangLo->verifyOrderPayment([]);
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
            'partner' => '802442',
            'ordernumber' => '201802050000008889',
            'orderstatus' => '2',
            'paymoney' => '10.0000',
            'sysnumber' => '20180205164833662571000000',
            'attach' => '',
            'sign' => '9f87bfa6d4f6cdfd8b9285699a4affc8',
        ];

        $heFuWangLo = new HeFuWangLo();
        $heFuWangLo->setPrivateKey('test');
        $heFuWangLo->setOptions($options);
        $heFuWangLo->verifyOrderPayment([]);
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
            'partner' => '802442',
            'ordernumber' => '201802050000008889',
            'orderstatus' => '1',
            'paymoney' => '10.0000',
            'sysnumber' => '20180205164833662571000000',
            'attach' => '',
            'sign' => 'b38fceec095672c7d05bdad128818236',
        ];

        $entry = ['id' => '201503220000000555'];

        $heFuWangLo = new HeFuWangLo();
        $heFuWangLo->setPrivateKey('test');
        $heFuWangLo->setOptions($options);
        $heFuWangLo->verifyOrderPayment($entry);
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
            'partner' => '802442',
            'ordernumber' => '201802050000008889',
            'orderstatus' => '1',
            'paymoney' => '10.0000',
            'sysnumber' => '20180205164833662571000000',
            'attach' => '',
            'sign' => 'b38fceec095672c7d05bdad128818236',
        ];

        $entry = [
            'id' => '201802050000008889',
            'amount' => '15.00',
        ];

        $heFuWangLo = new HeFuWangLo();
        $heFuWangLo->setPrivateKey('test');
        $heFuWangLo->setOptions($options);
        $heFuWangLo->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果
     */
    public function testReturnSuccess()
    {
        $options = [
            'partner' => '802442',
            'ordernumber' => '201802050000008889',
            'orderstatus' => '1',
            'paymoney' => '10.0000',
            'sysnumber' => '20180205164833662571000000',
            'attach' => '',
            'sign' => 'b38fceec095672c7d05bdad128818236',
        ];

        $entry = [
            'id' => '201802050000008889',
            'amount' => '10.00',
        ];

        $heFuWangLo = new HeFuWangLo();
        $heFuWangLo->setPrivateKey('test');
        $heFuWangLo->setOptions($options);
        $heFuWangLo->verifyOrderPayment($entry);

        $this->assertEquals('ok', $heFuWangLo->getMsg());
    }
}
