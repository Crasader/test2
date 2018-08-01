<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\YuLeTong;

class YuLeTongTest extends DurianTestCase
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

        $yuLeTong = new YuLeTong();
        $yuLeTong->getVerifyData();
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

        $yuLeTong = new YuLeTong();
        $yuLeTong->setPrivateKey('1234');
        $yuLeTong->setOptions($sourceData);
        $yuLeTong->getVerifyData();
    }

    /**
     * 測試支付加密時帶入支付平台不支援的銀行
     */
    public function testPayUnsupportedPaymentVendor()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'PaymentVendor is not supported by PaymentGateway',
            180066
        );

        $sourceData = [
            'number' => '330887862088',
            'paymentVendorId' => '999',
            'amount' => '0.01',
            'orderId' => '201711300000007863',
            'notify_url' => 'http://two123.comxa.com/',
            'ip' => '127.0.0.1',
        ];

        $yuLeTong = new YuLeTong();
        $yuLeTong->setPrivateKey('1234');
        $yuLeTong->setOptions($sourceData);
        $yuLeTong->getVerifyData();
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
            'number' => '330887862088',
            'paymentVendorId' => '1092',
            'amount' => '0.01',
            'orderId' => '201711300000007863',
            'notify_url' => 'http://two123.comxa.com/',
            'ip' => '127.0.0.1',
            'postUrl' => '',
        ];

        $yuLeTong = new YuLeTong();
        $yuLeTong->setPrivateKey('1234');
        $yuLeTong->setOptions($sourceData);
        $yuLeTong->getVerifyData();
    }

    /**
     * 測試支付加密
     */
    public function testPay()
    {
        $sourceData = [
            'number' => '330887862088',
            'paymentVendorId' => '1092',
            'amount' => '0.01',
            'orderId' => '201711300000007863',
            'notify_url' => 'http://two123.comxa.com/',
            'ip' => '127.0.0.1',
            'postUrl' => 'https://gateway.zbpay365.com/GateWay/Pay',
        ];

        $yuLeTong = new YuLeTong();
        $yuLeTong->setPrivateKey('1234');
        $yuLeTong->setOptions($sourceData);
        $encodeData = $yuLeTong->getVerifyData();

        $this->assertEquals('330887862088', $encodeData['params']['merchant_no']);
        $this->assertEquals('201711300000007863', $encodeData['params']['order_no']);
        $this->assertSame('0.01', $encodeData['params']['amount']);
        $this->assertEquals('alipay_qr', $encodeData['params']['channel']);
        $this->assertEquals('http://two123.comxa.com/', $encodeData['params']['notify_url']);
        $this->assertEquals('http://two123.comxa.com/', $encodeData['params']['result_url']);
        $this->assertEquals('127.0.0.1', $encodeData['params']['c_ip']);
        $this->assertEquals('', $encodeData['params']['extra_param']);
        $this->assertEquals('472a7f0a5e0baed333181b33648980d5', $encodeData['params']['sign']);

        $this->assertEquals('https://gateway.zbpay365.com/GateWay/Pay/ylt/api/v1/qrPay', $encodeData['post_url']);
        $this->assertEquals('GET', $yuLeTong->getPayMethod());
    }

    /**
     * 測試手機支付
     */
    public function testPhonePay()
    {
        $sourceData = [
            'number' => '330887862088',
            'paymentVendorId' => '1104',
            'amount' => '1',
            'orderId' => '201711300000007863',
            'notify_url' => 'http://two123.comxa.com/',
            'ip' => '127.0.0.1',
            'postUrl' => 'https://gateway.zbpay365.com/GateWay/Pay',
        ];

        $yuLeTong = new YuLeTong();
        $yuLeTong->setPrivateKey('1234');
        $yuLeTong->setOptions($sourceData);
        $encodeData = $yuLeTong->getVerifyData();

        $this->assertEquals('330887862088', $encodeData['params']['merchant_no']);
        $this->assertEquals('201711300000007863', $encodeData['params']['order_no']);
        $this->assertSame('1.00', $encodeData['params']['amount']);
        $this->assertEquals('qq_wap', $encodeData['params']['channel']);
        $this->assertEquals('http://two123.comxa.com/', $encodeData['params']['notify_url']);
        $this->assertEquals('http://two123.comxa.com/', $encodeData['params']['result_url']);
        $this->assertEquals('127.0.0.1', $encodeData['params']['c_ip']);
        $this->assertEquals('', $encodeData['params']['extra_param']);
        $this->assertEquals('93e65debeb7117e4f29966e4ac99171e', $encodeData['params']['sign']);

        $this->assertEquals('https://gateway.zbpay365.com/GateWay/Pay/ylt/api/v1/activePay', $encodeData['post_url']);
        $this->assertEquals('GET', $yuLeTong->getPayMethod());
    }

    /**
     * 測試返回時沒有帶入key的情況
     */
    public function testReturnWithoutKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $yuLeTong = new YuLeTong();
        $yuLeTong->verifyOrderPayment([]);
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

        $yuLeTong = new YuLeTong();
        $yuLeTong->setPrivateKey('1234');
        $yuLeTong->setOptions([]);
        $yuLeTong->verifyOrderPayment([]);
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
            'amount' => '1000',
            'result_code' => 'success',
            'merchant_no' => '330887862088',
            'extra_param' => '123',
            'channel' => 'alipay_pc',
            'ylt_order_no' => '11130171903325147806',
            'order_no' => '201711300000007863',
        ];

        $yuLeTong = new YuLeTong();
        $yuLeTong->setPrivateKey('1234');
        $yuLeTong->setOptions($sourceData);
        $yuLeTong->verifyOrderPayment([]);
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
            'sign' => 'edd929117807da0ce7abec31b525d83b',
            'amount' => '1000',
            'result_code' => 'success',
            'merchant_no' => '330887862088',
            'extra_param' => '123',
            'channel' => 'alipay_pc',
            'ylt_order_no' => '11130171903325147806',
            'order_no' => '201711300000007863',
        ];

        $yuLeTong = new YuLeTong();
        $yuLeTong->setPrivateKey('1234');
        $yuLeTong->setOptions($sourceData);
        $yuLeTong->verifyOrderPayment([]);
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
            'sign' => '66dcad71eb0c44164585420513b79642',
            'amount' => '1000',
            'result_code' => 'fail',
            'merchant_no' => '330887862088',
            'extra_param' => '123',
            'channel' => 'alipay_pc',
            'ylt_order_no' => '11130171903325147806',
            'order_no' => '201711300000007863',
        ];

        $yuLeTong = new YuLeTong();
        $yuLeTong->setPrivateKey('1234');
        $yuLeTong->setOptions($sourceData);
        $yuLeTong->verifyOrderPayment([]);
    }

    /**
     * 測試返回時單號不正確的情況
     */
    public function testReturnOrderIdError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $sourceData = [
            'sign' => '66dcad71eb0c44164585420513b79642',
            'amount' => '1000',
            'result_code' => 'success',
            'merchant_no' => '330887862088',
            'extra_param' => '123',
            'channel' => 'alipay_pc',
            'ylt_order_no' => '11130171903325147806',
            'order_no' => '201711300000007863',
        ];


        $entry = ['id' => '201606220000002806'];

        $yuLeTong = new YuLeTong();
        $yuLeTong->setPrivateKey('1234');
        $yuLeTong->setOptions($sourceData);
        $yuLeTong->verifyOrderPayment($entry);
    }

    /**
     * 測試返回時金額不正確的情況
     */
    public function testReturnOrderAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $sourceData = [
            'sign' => '66dcad71eb0c44164585420513b79642',
            'amount' => '1000',
            'result_code' => 'success',
            'merchant_no' => '330887862088',
            'extra_param' => '123',
            'channel' => 'alipay_pc',
            'ylt_order_no' => '11130171903325147806',
            'order_no' => '201711300000007863',
        ];

        $entry = [
            'id' => '201711300000007863',
            'amount' => '1.0000',
        ];

        $yuLeTong = new YuLeTong();
        $yuLeTong->setPrivateKey('1234');
        $yuLeTong->setOptions($sourceData);
        $yuLeTong->verifyOrderPayment($entry);
    }

    /**
     * 測試返回支付驗證成功
     */
    public function testReturnSuccess()
    {
        $sourceData = [
            'sign' => '19f8899036e6020a25b4e91afb6de018',
            'amount' => '10',
            'result_code' => 'success',
            'merchant_no' => '330887862088',
            'extra_param' => '123',
            'channel' => 'alipay_pc',
            'ylt_order_no' => '11130171903325147806',
            'order_no' => '201711300000007863',
        ];

        $entry = [
            'id' => '201711300000007863',
            'amount' => '10.00',
        ];

        $yuLeTong = new YuLeTong();
        $yuLeTong->setPrivateKey('1234');
        $yuLeTong->setOptions($sourceData);
        $yuLeTong->verifyOrderPayment($entry);

        $this->assertEquals('success', $yuLeTong->getMsg());
    }
}
