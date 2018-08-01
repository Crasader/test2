<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\AliPay;

class AliPayTest extends DurianTestCase
{
    /**
     * 測試加密基本參數設定沒有帶入privateKey的情況
     */
    public function testSetEncodeSourceNoPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $aliPay = new AliPay();
        $aliPay->getVerifyData();
    }

    /**
     * 測試加密基本參數設定未指定支付參數
     */
    public function testSetEncodeSourceNoPayParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No pay parameter specified',
            180145
        );

        $aliPay = new AliPay();
        $aliPay->setPrivateKey('556448bge271132c911ag97afacg0332a8389728cd');

        $sourceData = ['number' => ''];

        $aliPay->setOptions($sourceData);
        $aliPay->getVerifyData();
    }

    /**
     * 測試加密基本參數設定帶入不支援的銀行
     */
    public function testSetEncodeSourceUnsupportedPaymentVendor()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'PaymentVendor is not supported by PaymentGateway',
            180066
        );

        $aliPay = new AliPay();
        $aliPay->setPrivateKey('556448bge271132c911ag97afacg0332a8389728cd');

        $sourceData = [
            'number' => '100000000006463',
            'orderId' => '201404070013026085',
            'amount' => '590',
            'notify_url' => 'http://esball.org/app/member/pay_online2/pay_result.php',
            'paymentVendorId' => '999',
            'merchantId' => '12345',
            'domain' => '6',
        ];

        $aliPay->setOptions($sourceData);
        $aliPay->getVerifyData();
    }

    /**
     * 測試加密,找不到商家的SellerEmail附加設定值
     */
    public function testGetVerifyDataButNoSellerEmailSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No merchant extra value specified',
            180143
        );

        $aliPay = new AliPay();
        $aliPay->setPrivateKey('556448bge271132c911ag97afacg0332a8389728cd');

        $sourceData = [
            'number' => '100000000006463',
            'notify_url' => 'http://esball.org/app/member/pay_online2/pay_result.php',
            'orderId' => '201404070013026085',
            'amount' => '590',
            'paymentVendorId' => '12',
            'merchant_extra' => [],
            'merchantId' => '12345',
            'domain' => '6',
        ];

        $aliPay->setOptions($sourceData);
        $aliPay->getVerifyData();
    }

    /**
     * 測試加密
     */
    public function testGetEncodeData()
    {
        $sourceData = [
            'number' => '100000000006463',
            'notify_url' => 'http://esball.org/app/member/pay_online2/pay_result.php',
            'orderId' => '201404070013026085',
            'amount' => '590',
            'paymentVendorId' => '12', //'12' => 'CEBBANK'
            'merchant_extra' => ['alipayUserName' => '1265716844@qq.com'],
            'merchantId' => '12345',
            'domain' => '6',
        ];

        $aliPay = new AliPay();
        $aliPay->setPrivateKey('556448bge271132c911ag97afacg0332a8389728cd');
        $aliPay->setOptions($sourceData);
        $encodeData = $aliPay->getVerifyData();

        $notifyUrl = sprintf(
            '%s?pay_system=%s&hallid=%s',
            $sourceData['notify_url'],
            $sourceData['merchantId'],
            $sourceData['domain']
        );

        $this->assertEquals($sourceData['number'], $encodeData['partner']);
        $this->assertEquals($notifyUrl, $encodeData['notify_url']);
        $this->assertEquals($sourceData['orderId'], $encodeData['out_trade_no']);
        $this->assertSame('590.00', $encodeData['total_fee']);
        $this->assertEquals('CEBBANK', $encodeData['defaultbank']);
        $this->assertEquals('1265716844@qq.com', $encodeData['seller_email']);
        $this->assertEquals('9a890489f8262c117b9466cf14299750', $encodeData['sign']);
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

        $aliPay = new AliPay();

        $aliPay->verifyOrderPayment([]);
    }

    /**
     * 測試解密驗證時支付平台缺少回傳參數(測試trade_status:交易狀態)
     */
    public function testReturnNoReturnParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $aliPay = new AliPay();
        $aliPay->setPrivateKey('556448bge271132c911ag97afacg0332a8389728cd1cg05037487d20829845a2');

        $sourceData = [
            'body'                 => 'aaaa9965',
            'subject'              => 'aaaa9965',
            'sign_type'            => 'MD5',
            'is_total_fee_adjust'  => '0',
            'notify_type'          => 'WAIT_TRIGGER',
            'out_trade_no'         => '201404070013044019',
            'buyer_email'          => '',
            'total_fee'            => '110.00',
            'seller_actions'       => 'SEND_GOODS',
            'quantity'             => '1',
            'buyer_id'             => '',
            'trade_no'             => '101404072598811',
            'notify_time'          => '2014-04-07 03:02:44',
            'gmt_payment'          => '2014-04-07 03:02:44',
            'discount'             => '0.00',
            'sign'                 => 'e96df31c224fc0555147b5e9d8e186c6',
            'is_success'           => 'T',
            'gmt_create'           => '2014-04-07 03:01:53',
            'price'                => '110.00',
            'seller_id'            => '100000000006463',
            'seller_email'         => '1265716844@qq.com',
            'notify_id'            => '9ca29d053388463ea69e985b8782ef3a',
            'gmt_logistics_modify' => '2014-04-07 03:02:47',
            'payment_type'         => '1',
        ];

        $aliPay->setOptions($sourceData);
        $aliPay->verifyOrderPayment([]);
    }

    /**
     * 測試解密驗證時支付平台缺少回傳參數(測試sign:加密簽名)
     */
    public function testReturnWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $aliPay = new AliPay();
        $aliPay->setPrivateKey('556448bge271132c911ag97afacg0332a8389728cd1cg05037487d20829845a2');

        $sourceData = [
            'body'                 => 'aaaa9965',
            'subject'              => 'aaaa9965',
            'sign_type'            => 'MD5',
            'is_total_fee_adjust'  => '0',
            'notify_type'          => 'WAIT_TRIGGER',
            'out_trade_no'         => '201404070013044019',
            'buyer_email'          => '',
            'total_fee'            => '110.00',
            'seller_actions'       => 'SEND_GOODS',
            'quantity'             => '1',
            'buyer_id'             => '',
            'trade_no'             => '101404072598811',
            'notify_time'          => '2014-04-07 03:02:44',
            'gmt_payment'          => '2014-04-07 03:02:44',
            'trade_status'         => 'TRADE_FINISHED',
            'discount'             => '0.00',
            'is_success'           => 'T',
            'gmt_create'           => '2014-04-07 03:01:53',
            'price'                => '110.00',
            'seller_id'            => '100000000006463',
            'seller_email'         => '1265716844@qq.com',
            'notify_id'            => '9ca29d053388463ea69e985b8782ef3a',
            'gmt_logistics_modify' => '2014-04-07 03:02:47',
            'payment_type'         => '1',
        ];

        $aliPay->setOptions($sourceData);
        $aliPay->verifyOrderPayment([]);
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

        $aliPay = new AliPay();
        $aliPay->setPrivateKey('556448bge271132c911ag97afacg0332a8389728cd1cg05037487d20829845a2');

        $sourceData = [
            'body'                 => 'aaaa9965',
            'subject'              => 'aaaa9965',
            'sign_type'            => 'MD5',
            'is_total_fee_adjust'  => '0',
            'notify_type'          => 'WAIT_TRIGGER',
            'out_trade_no'         => '201404070013044019',
            'buyer_email'          => '',
            'total_fee'            => '110.00',
            'seller_actions'       => 'SEND_GOODS',
            'quantity'             => '1',
            'buyer_id'             => '',
            'trade_no'             => '101404072598811',
            'notify_time'          => '2014-04-07 03:02:44',
            'gmt_payment'          => '2014-04-07 03:02:44',
            'trade_status'         => 'TRADE_FINISHED',
            'discount'             => '0.00',
            'sign'                 => 'e96df31c224fw945d147b5e9d8e186c6',
            'is_success'           => 'T',
            'gmt_create'           => '2014-04-07 03:01:53',
            'price'                => '110.00',
            'seller_id'            => '100000000006463',
            'seller_email'         => '1265716844@qq.com',
            'notify_id'            => '9ca29d053388463ea69e985b8782ef3a',
            'gmt_logistics_modify' => '2014-04-07 03:02:47',
            'payment_type'         => '1',
        ];

        $aliPay->setOptions($sourceData);
        $aliPay->verifyOrderPayment([]);
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

        $aliPay = new AliPay();
        $aliPay->setPrivateKey('556448bge271132c911ag97afacg0332a8389728cd1cg05037487d20829845a2');

        $sourceData = [
            'sign'                => 'd461dfe09633ecbc31f7ae096d1bb5fb',
            'sign_type'           => 'MD5',
            'trade_no'            => '101404072598811',
            'out_trade_no'        => '201404070013044019',
            'total_fee'           => '110.00',
            'subject'             => 'aaaa9965',
            'seller_email'        => '1265716844@qq.com',
            'seller_id'           => '100000000006463',
            'buyer_email'         => '4486175621@qq.com',
            'buyer_id'            => '364600000000001',
            'trade_status'        => 'TRADE_FAILED',
            'notify_id'           => '9ca29d053388463ea69e985b8782ef3a',
            'notify_time'         => '2014-04-07 03:02:44',
            'notify_type'         => 'WAIT_TRIGGER',
            'payment_type'        => '1',
            'body'                => '',
            'extra_common'        => '',
            'price'               => '',
            'quantity'            => '',
            'discount'            => '',
            'gmt_create'          => '',
            'gmt_payment'         => '',
            'gmt_close'           => '',
            'is_total_fee_adjust' => '',
            'use_coupon'          => '',
            'exterface'           => '',
            'is_success'          => ''
        ];

        $aliPay->setOptions($sourceData);
        $aliPay->verifyOrderPayment([]);
    }

    /**
     * 測試線上支付單號不正確的情況
     */
    public function testOrderIdError()
    {
        $this->setExpectedException('BB\DurianBundle\Exception\PaymentException', 'Order Id error', 180061);

        $aliPay = new AliPay();
        $aliPay->setPrivateKey('556448bge271132c911ag97afacg0332a8389728cd1cg05037487d20829845a2');

        $sourceData = [
            'sign'                => '9f1ef0aac30056400da531158f7732a8',
            'sign_type'           => 'MD5',
            'trade_no'            => '101404072598811',
            'out_trade_no'        => '201404070013044019',
            'total_fee'           => '110.00',
            'subject'             => 'aaaa9965',
            'seller_email'        => '1265716844@qq.com',
            'seller_id'           => '100000000006463',
            'buyer_email'         => '4486175621@qq.com',
            'buyer_id'            => '364600000000001',
            'trade_status'        => 'TRADE_FINISHED',
            'notify_id'           => '9ca29d053388463ea69e985b8782ef3a',
            'notify_time'         => '2014-04-07 03:02:44',
            'notify_type'         => 'WAIT_TRIGGER',
            'payment_type'        => '1',
            'body'                => '',
            'extra_common'        => '',
            'price'               => '',
            'quantity'            => '',
            'discount'            => '',
            'gmt_create'          => '',
            'gmt_payment'         => '',
            'gmt_close'           => '',
            'is_total_fee_adjust' => '',
            'use_coupon'          => '',
            'exterface'           => '',
            'is_success'          => ''
        ];

        $entry = ['id' => '20140113143143'];

        $aliPay->setOptions($sourceData);
        $aliPay->verifyOrderPayment($entry);
    }

    /**
     * 測試線上支付金額不正確的情況
     */
    public function testOrderAmountError()
    {
        $this->setExpectedException('BB\DurianBundle\Exception\PaymentException', 'Order Amount error', 180058);

        $aliPay = new AliPay();
        $aliPay->setPrivateKey('556448bge271132c911ag97afacg0332a8389728cd1cg05037487d20829845a2');

        $sourceData = [
            'sign'                => '9f1ef0aac30056400da531158f7732a8',
            'sign_type'           => 'MD5',
            'trade_no'            => '101404072598811',
            'out_trade_no'        => '201404070013044019',
            'total_fee'           => '110.00',
            'subject'             => 'aaaa9965',
            'seller_email'        => '1265716844@qq.com',
            'seller_id'           => '100000000006463',
            'buyer_email'         => '4486175621@qq.com',
            'buyer_id'            => '364600000000001',
            'trade_status'        => 'TRADE_FINISHED',
            'notify_id'           => '9ca29d053388463ea69e985b8782ef3a',
            'notify_time'         => '2014-04-07 03:02:44',
            'notify_type'         => 'WAIT_TRIGGER',
            'payment_type'        => '1',
            'body'                => '',
            'extra_common'        => '',
            'price'               => '',
            'quantity'            => '',
            'discount'            => '',
            'gmt_create'          => '',
            'gmt_payment'         => '',
            'gmt_close'           => '',
            'is_total_fee_adjust' => '',
            'use_coupon'          => '',
            'exterface'           => '',
            'is_success'          => ''
        ];

        $entry = [
            'id' => '201404070013044019',
            'amount' => '115.00'
        ];

        $aliPay->setOptions($sourceData);
        $aliPay->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testPaySuccess()
    {
        $aliPay = new AliPay();
        $aliPay->setPrivateKey('556448bge271132c911ag97afacg0332a8389728cd1cg05037487d20829845a2');

        $sourceData = [
            'sign'                => '9f1ef0aac30056400da531158f7732a8',
            'sign_type'           => 'MD5',
            'trade_no'            => '101404072598811',
            'out_trade_no'        => '201404070013044019',
            'total_fee'           => '110.00',
            'subject'             => 'aaaa9965',
            'seller_email'        => '1265716844@qq.com',
            'seller_id'           => '100000000006463',
            'buyer_email'         => '4486175621@qq.com',
            'buyer_id'            => '364600000000001',
            'trade_status'        => 'TRADE_FINISHED',
            'notify_id'           => '9ca29d053388463ea69e985b8782ef3a',
            'notify_time'         => '2014-04-07 03:02:44',
            'notify_type'         => 'WAIT_TRIGGER',
            'payment_type'        => '1',
            'body'                => '',
            'extra_common'        => '',
            'price'               => '',
            'quantity'            => '',
            'discount'            => '',
            'gmt_create'          => '',
            'gmt_payment'         => '',
            'gmt_close'           => '',
            'is_total_fee_adjust' => '',
            'use_coupon'          => '',
            'exterface'           => '',
            'is_success'          => ''
        ];

        $entry = [
            'id' => '201404070013044019',
            'amount' => '110.00'
        ];

        $aliPay->setOptions($sourceData);
        $aliPay->verifyOrderPayment($entry);

        $this->assertEquals('success', $aliPay->getMsg());
    }
}
