<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\ChickenPay;

class ChickenPayTest extends DurianTestCase
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

        $chickenPay = new ChickenPay();
        $chickenPay->getVerifyData();
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

        $chickenPay = new ChickenPay();
        $chickenPay->setPrivateKey('test');
        $chickenPay->getVerifyData();
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
            'number' => '1450671116264683',
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '999',
            'orderId' => '201709140000004664',
            'amount' => '0.10',
            'username' => 'php1test',
            'orderCreateDate' => '2017-06-06 10:06:06',
        ];

        $chickenPay = new ChickenPay();
        $chickenPay->setPrivateKey('test');
        $chickenPay->setOptions($options);
        $chickenPay->getVerifyData();
    }

    /**
     * 測試支付寶手機支付未帶verify_url
     */
    public function testAliPayWithoutVerifyUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No verify_url specified',
            180140
        );

        $options = [
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '1098',
            'orderId' => '201709080000004557',
            'amount' => '1.00',
            'number' => '1450671116264683',
            'username' => 'php1test',
            'orderCreateDate' => '2017-06-06 10:06:06',
            'verify_url' => '',
        ];

        $chickenPay = new ChickenPay();
        $chickenPay->setPrivateKey('test');
        $chickenPay->setOptions($options);
        $encodeData = $chickenPay->getVerifyData();
    }

    /**
     * 測試支付寶手機支付成功
     */
    public function testAliPay()
    {
        $options = [
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '1098',
            'orderId' => '201709080000004557',
            'amount' => '1.00',
            'number' => '1450671116264683',
            'username' => 'php1test',
            'orderCreateDate' => '2017-06-06 10:06:06',
            'verify_url' => 'https://api.apialipay.com/gateway/pay.htm',
        ];

        $chickenPay = new ChickenPay();
        $chickenPay->setPrivateKey('test');
        $chickenPay->setOptions($options);
        $encodeData = $chickenPay->getVerifyData();

        $this->assertEquals($options['verify_url'], $encodeData['post_url']);
        $this->assertEquals($options['number'], $encodeData['params']['merchant_no']);
        $this->assertEquals('1.0.0', $encodeData['params']['version']);
        $this->assertEquals($options['orderId'], $encodeData['params']['out_trade_no']);
        $this->assertEquals('alipaywap', $encodeData['params']['payment_type']);
        $this->assertEquals($options['notify_url'], $encodeData['params']['notify_url']);
        $this->assertEquals($options['notify_url'], $encodeData['params']['page_url']);
        $this->assertEquals($options['amount'], $encodeData['params']['total_fee']);
        $this->assertEquals('20170606100606', $encodeData['params']['trade_time']);
        $this->assertEquals($options['username'], $encodeData['params']['user_account']);
        $this->assertEquals('', $encodeData['params']['body']);
        $this->assertEquals('e92793ff891eb5e3c2e0be07905f034e', $encodeData['params']['sign']);
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $options = [
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '1090',
            'orderId' => '201709080000004557',
            'amount' => '1.00',
            'number' => '1450671116264683',
            'username' => 'php1test',
            'orderCreateDate' => '2017-06-06 10:06:06',
        ];

        $chickenPay = new ChickenPay();
        $chickenPay->setPrivateKey('test');
        $chickenPay->setOptions($options);
        $encodeData = $chickenPay->getVerifyData();

        $this->assertEquals($options['number'], $encodeData['merchant_no']);
        $this->assertEquals('1.0.0', $encodeData['version']);
        $this->assertEquals($options['orderId'], $encodeData['out_trade_no']);
        $this->assertEquals('wxapp', $encodeData['payment_type']);
        $this->assertEquals($options['notify_url'], $encodeData['notify_url']);
        $this->assertEquals($options['notify_url'], $encodeData['page_url']);
        $this->assertEquals($options['amount'], $encodeData['total_fee']);
        $this->assertEquals('20170606100606', $encodeData['trade_time']);
        $this->assertEquals($options['username'], $encodeData['user_account']);
        $this->assertEquals('', $encodeData['body']);
        $this->assertEquals('a6220aaaae954dd27647109e35e9e028', $encodeData['sign']);
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

        $chickenPay = new ChickenPay();
        $chickenPay->verifyOrderPayment([]);
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

        $chickenPay = new ChickenPay();
        $chickenPay->setPrivateKey('test');
        $chickenPay->verifyOrderPayment([]);
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

        $options = [
            'merchant_no' => '23888',
            'out_trade_no' => '201803080000009999',
            'trade_no' => '2388818030819341792885831',
            'user_account' => 'php1test',
            'body' => '',
            'total_fee' => '1',
            'obtain_fee' => '1',
            'notify_time' => '2018-03-08 19:34:46',
            'trade_status' => 'SUCCESS',
            'version' => '1.0.0',
            'payment_type' => 'wxapp',
        ];

        $chickenPay = new ChickenPay();
        $chickenPay->setPrivateKey('test');
        $chickenPay->setOptions($options);
        $chickenPay->verifyOrderPayment([]);
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
            'merchant_no' => '23888',
            'out_trade_no' => '201803080000009999',
            'trade_no' => '2388818030819341792885831',
            'user_account' => 'php1test',
            'body' => '',
            'total_fee' => '1',
            'obtain_fee' => '1',
            'notify_time' => '2018-03-08 19:34:46',
            'trade_status' => 'SUCCESS',
            'version' => '1.0.0',
            'payment_type' => 'wxapp',
            'sign' => '299d7c6f15005ece9165929963b95cc5',
        ];

        $chickenPay = new ChickenPay();
        $chickenPay->setPrivateKey('test');
        $chickenPay->setOptions($options);
        $chickenPay->verifyOrderPayment([]);
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
            'merchant_no' => '23888',
            'out_trade_no' => '201803080000009999',
            'trade_no' => '2388818030819341792885831',
            'user_account' => 'php1test',
            'body' => '',
            'total_fee' => '1',
            'obtain_fee' => '1',
            'notify_time' => '2018-03-08 19:34:46',
            'trade_status' => 'Fail',
            'version' => '1.0.0',
            'payment_type' => 'wxapp',
            'sign' => '2b42e6ec9964a163b48800fa6dc11e6e',
        ];

        $chickenPay = new ChickenPay();
        $chickenPay->setPrivateKey('test');
        $chickenPay->setOptions($options);
        $chickenPay->verifyOrderPayment([]);
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
            'merchant_no' => '23888',
            'out_trade_no' => '201803080000009999',
            'trade_no' => '2388818030819341792885831',
            'user_account' => 'php1test',
            'body' => '',
            'total_fee' => '1',
            'obtain_fee' => '1',
            'notify_time' => '2018-03-08 19:34:46',
            'trade_status' => 'SUCCESS',
            'version' => '1.0.0',
            'payment_type' => 'wxapp',
            'sign' => '1d78cabb6a7a642f45500e5ac83c3968',
        ];

        $entry = ['id' => '201706050000001234'];

        $chickenPay = new ChickenPay();
        $chickenPay->setPrivateKey('test');
        $chickenPay->setOptions($options);
        $chickenPay->verifyOrderPayment($entry);
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
            'merchant_no' => '23888',
            'out_trade_no' => '201803080000009999',
            'trade_no' => '2388818030819341792885831',
            'user_account' => 'php1test',
            'body' => '',
            'total_fee' => '1',
            'obtain_fee' => '1',
            'notify_time' => '2018-03-08 19:34:46',
            'trade_status' => 'SUCCESS',
            'version' => '1.0.0',
            'payment_type' => 'wxapp',
            'sign' => '1d78cabb6a7a642f45500e5ac83c3968',
        ];

        $entry = [
            'id' => '201803080000009999',
            'amount' => '15.00',
        ];

        $chickenPay = new ChickenPay();
        $chickenPay->setPrivateKey('test');
        $chickenPay->setOptions($options);
        $chickenPay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回驗證成功
     */
    public function testReturnSuccess()
    {
        $options = [
            'merchant_no' => '23888',
            'out_trade_no' => '201803080000009999',
            'trade_no' => '2388818030819341792885831',
            'user_account' => 'php1test',
            'body' => '',
            'total_fee' => '1',
            'obtain_fee' => '1',
            'notify_time' => '2018-03-08 19:34:46',
            'trade_status' => 'SUCCESS',
            'version' => '1.0.0',
            'payment_type' => 'wxapp',
            'sign' => '1d78cabb6a7a642f45500e5ac83c3968',
        ];

        $entry = [
            'id' => '201803080000009999',
            'amount' => '1',
        ];

        $chickenPay = new ChickenPay();
        $chickenPay->setPrivateKey('test');
        $chickenPay->setOptions($options);
        $chickenPay->verifyOrderPayment($entry);

        $this->assertEquals('success', $chickenPay->getMsg());
    }
}
