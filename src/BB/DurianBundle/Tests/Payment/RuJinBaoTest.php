<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\RuJinBao;

class RuJinBaoTest extends DurianTestCase
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

        $ruJinBao = new RuJinBao();
        $ruJinBao->getVerifyData();
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

        $ruJinBao = new RuJinBao();
        $ruJinBao->setPrivateKey('test');
        $ruJinBao->getVerifyData();
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

        $ruJinBao = new RuJinBao();
        $ruJinBao->setPrivateKey('test');
        $ruJinBao->setOptions($options);
        $ruJinBao->getVerifyData();
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

        $ruJinBao = new RuJinBao();
        $ruJinBao->setPrivateKey('test');
        $ruJinBao->setOptions($options);
        $encodeData = $ruJinBao->getVerifyData();

        $this->assertEquals($options['number'], $encodeData['merchant_no']);
        $this->assertEquals('1.0.3', $encodeData['version']);
        $this->assertEquals($options['orderId'], $encodeData['out_trade_no']);
        $this->assertEquals('wxpay', $encodeData['payment_type']);
        $this->assertEquals('', $encodeData['payment_bank']);
        $this->assertEquals($options['notify_url'], $encodeData['notify_url']);
        $this->assertEquals($options['notify_url'], $encodeData['page_url']);
        $this->assertEquals($options['amount'], $encodeData['total_fee']);
        $this->assertEquals('20170606100606', $encodeData['trade_time']);
        $this->assertEquals($options['username'], $encodeData['user_account']);
        $this->assertEquals('', $encodeData['body']);
        $this->assertEquals('', $encodeData['channel']);
        $this->assertEquals('ee5dd61ad6348d9e355f3672be59f9ad', $encodeData['sign']);
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

        $ruJinBao = new RuJinBao();
        $ruJinBao->verifyOrderPayment([]);
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

        $ruJinBao = new RuJinBao();
        $ruJinBao->setPrivateKey('test');
        $ruJinBao->verifyOrderPayment([]);
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
            'body' => '',
            'trade_no' => '1709141035380098829901240',
            'notify_time' => '20170914103714',
            'merchant_no' => '1450671116264683',
            'total_fee' => '0.10',
            'out_trade_no' => '201709140000004664',
            'obtain_fee' => '0.10',
            'trade_status' => 'SUCCESS',
            'payment_type' => 'wxpay_xj',
            'payment_bank' => '',
            'version' => '1.0.3',
        ];

        $ruJinBao = new RuJinBao();
        $ruJinBao->setPrivateKey('test');
        $ruJinBao->setOptions($options);
        $ruJinBao->verifyOrderPayment([]);
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
            'sign' => 'ebd46af23e838d8c1301331980a6c52a',
            'body' => '',
            'trade_no' => '1709141035380098829901240',
            'notify_time' => '20170914103714',
            'merchant_no' => '1450671116264683',
            'total_fee' => '0.10',
            'out_trade_no' => '201709140000004664',
            'obtain_fee' => '0.10',
            'trade_status' => 'SUCCESS',
            'payment_type' => 'wxpay_xj',
            'payment_bank' => '',
            'version' => '1.0.3',
        ];

        $ruJinBao = new RuJinBao();
        $ruJinBao->setPrivateKey('test');
        $ruJinBao->setOptions($options);
        $ruJinBao->verifyOrderPayment([]);
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
            'sign' => '5c23169b19b820e299b87d9cbe5d004f',
            'body' => '',
            'trade_no' => '1709141035380098829901240',
            'notify_time' => '20170914103714',
            'merchant_no' => '1450671116264683',
            'total_fee' => '0.10',
            'out_trade_no' => '201709140000004664',
            'obtain_fee' => '0.10',
            'trade_status' => 'Fail',
            'payment_type' => 'wxpay_xj',
            'payment_bank' => '',
            'version' => '1.0.3',
        ];

        $ruJinBao = new RuJinBao();
        $ruJinBao->setPrivateKey('test');
        $ruJinBao->setOptions($options);
        $ruJinBao->verifyOrderPayment([]);
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
            'sign' => 'e3af27d0bb3ac08b305f8da842bfcfe3',
            'body' => '',
            'trade_no' => '1709141035380098829901240',
            'notify_time' => '20170914103714',
            'merchant_no' => '1450671116264683',
            'total_fee' => '0.10',
            'out_trade_no' => '201709140000004664',
            'obtain_fee' => '0.10',
            'trade_status' => 'SUCCESS',
            'payment_type' => 'wxpay_xj',
            'payment_bank' => '',
            'version' => '1.0.3',
        ];

        $entry = ['id' => '201706050000001234'];

        $ruJinBao = new RuJinBao();
        $ruJinBao->setPrivateKey('test');
        $ruJinBao->setOptions($options);
        $ruJinBao->verifyOrderPayment($entry);
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
            'sign' => 'e3af27d0bb3ac08b305f8da842bfcfe3',
            'body' => '',
            'trade_no' => '1709141035380098829901240',
            'notify_time' => '20170914103714',
            'merchant_no' => '1450671116264683',
            'total_fee' => '0.10',
            'out_trade_no' => '201709140000004664',
            'obtain_fee' => '0.10',
            'trade_status' => 'SUCCESS',
            'payment_type' => 'wxpay_xj',
            'payment_bank' => '',
            'version' => '1.0.3',
        ];

        $entry = [
            'id' => '201709140000004664',
            'amount' => '15.00',
        ];

        $ruJinBao = new RuJinBao();
        $ruJinBao->setPrivateKey('test');
        $ruJinBao->setOptions($options);
        $ruJinBao->verifyOrderPayment($entry);
    }

    /**
     * 測試返回驗證成功
     */
    public function testReturnSuccess()
    {
        $options = [
            'sign' => 'e3af27d0bb3ac08b305f8da842bfcfe3',
            'body' => '',
            'trade_no' => '1709141035380098829901240',
            'notify_time' => '20170914103714',
            'merchant_no' => '1450671116264683',
            'total_fee' => '0.10',
            'out_trade_no' => '201709140000004664',
            'obtain_fee' => '0.10',
            'trade_status' => 'SUCCESS',
            'payment_type' => 'wxpay_xj',
            'payment_bank' => '',
            'version' => '1.0.3',
        ];

        $entry = [
            'id' => '201709140000004664',
            'amount' => '0.10',
        ];

        $ruJinBao = new RuJinBao();
        $ruJinBao->setPrivateKey('test');
        $ruJinBao->setOptions($options);
        $ruJinBao->verifyOrderPayment($entry);

        $this->assertEquals('success', $ruJinBao->getMsg());
    }
}
