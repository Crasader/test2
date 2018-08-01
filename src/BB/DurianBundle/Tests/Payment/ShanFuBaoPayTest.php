<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Payment\ShanFuBaoPay;
use BB\DurianBundle\Tests\DurianTestCase;

class ShanFuBaoPayTest extends DurianTestCase
{
    /**
     * 提交給支付平台時需要的參數
     *
     * @var array
     */
    private $option;

    /**
     * 支付成功時通知的參數
     *
     * @var array
     */
    private $returnResult;

    public function setUp()
    {
        parent::setUp();

        $this->option = [
            'number' => '9527',
            'paymentVendorId' => '1098',
            'amount' => '0.01',
            'orderId' => '201807010000046094',
            'orderCreateDate' => '2018-07-01 12:44:10',
            'notify_url' => 'http://www.seafood.help/',
        ];

        $this->returnResult = [
            'api_code' => '9527',
            'paysapi_id' => 'H701205787947798',
            'order_id' => '201807010000046094',
            'is_type' => 'alipay2',
            'price' => '0.01',
            'real_price' => '0.01',
            'mark' => '201807010000046094',
            'code' => '1',
            'sign' => '7A3932F07DCD513AFA21D2DF3D0BF375',
        ];
    }

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

        $shanFuBaoPay = new ShanFuBaoPay();
        $shanFuBaoPay->getVerifyData();
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

        $shanFuBaoPay = new ShanFuBaoPay();
        $shanFuBaoPay->setPrivateKey('test');
        $shanFuBaoPay->getVerifyData();
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

        $this->option['paymentVendorId'] = '999';

        $shanFuBaoPay = new ShanFuBaoPay();
        $shanFuBaoPay->setPrivateKey('test');
        $shanFuBaoPay->setOptions($this->option);
        $shanFuBaoPay->getVerifyData();
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $shanFuBaoPay = new ShanFuBaoPay();
        $shanFuBaoPay->setPrivateKey('test');
        $shanFuBaoPay->setOptions($this->option);
        $data = $shanFuBaoPay->getVerifyData();

        $this->assertEquals('html', $data['return_type']);
        $this->assertEquals('9527', $data['api_code']);
        $this->assertEquals('alipay5', $data['is_type']);
        $this->assertEquals('0.01', $data['price']);
        $this->assertEquals('201807010000046094', $data['order_id']);
        $this->assertEquals('1530420250', $data['time']);
        $this->assertEquals('201807010000046094', $data['mark']);
        $this->assertEquals('http://www.seafood.help/', $data['return_url']);
        $this->assertEquals('http://www.seafood.help/', $data['notify_url']);
        $this->assertEquals('7B03A34E8737A99794DC523F289013F2', $data['sign']);
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

        $shanFuBaoPay = new ShanFuBaoPay();
        $shanFuBaoPay->verifyOrderPayment([]);
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

        $entry = ['merchant_number' => '9527'];

        $shanFuBaoPay = new ShanFuBaoPay();
        $shanFuBaoPay->setPrivateKey('test');
        $shanFuBaoPay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回時未返回簽名
     */
    public function testReturnWithoutSignature()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        unset($this->returnResult['sign']);

        $entry = ['merchant_number' => '9527'];

        $shanFuBaoPay = new ShanFuBaoPay();
        $shanFuBaoPay->setPrivateKey('test');
        $shanFuBaoPay->setOptions($this->returnResult);
        $shanFuBaoPay->verifyOrderPayment($entry);
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

        $this->returnResult['sign'] = '1CCD2D59520E7F2AE4BC6F0A67CAF7E7';

        $entry = ['merchant_number' => '9527'];

        $shanFuBaoPay = new ShanFuBaoPay();
        $shanFuBaoPay->setPrivateKey('test');
        $shanFuBaoPay->setOptions($this->returnResult);
        $shanFuBaoPay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回時支付失敗
     */
    public function testReturnWithPaymentFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $this->returnResult['code'] = '2';
        $this->returnResult['sign'] = '5BE37BCB2DCC0B047B2173A552D70ACB';

        $entry = ['merchant_number' => '9527'];

        $shanFuBaoPay = new ShanFuBaoPay();
        $shanFuBaoPay->setPrivateKey('test');
        $shanFuBaoPay->setOptions($this->returnResult);
        $shanFuBaoPay->verifyOrderPayment($entry);
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

        $entry = [
            'merchant_number' => '9527',
            'id' => '9453',
        ];

        $shanFuBaoPay = new ShanFuBaoPay();
        $shanFuBaoPay->setPrivateKey('test');
        $shanFuBaoPay->setOptions($this->returnResult);
        $shanFuBaoPay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果為金額錯誤
     */
    public function testReturnOrderAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $entry = [
            'merchant_number' => '9527',
            'id' => '201807010000046094',
            'amount' => '123',
        ];

        $shanFuBaoPay = new ShanFuBaoPay();
        $shanFuBaoPay->setPrivateKey('test');
        $shanFuBaoPay->setOptions($this->returnResult);
        $shanFuBaoPay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果
     */
    public function testReturn()
    {
        $entry = [
            'merchant_number' => '9527',
            'id' => '201807010000046094',
            'amount' => '0.01',
        ];

        $shanFuBaoPay = new ShanFuBaoPay();
        $shanFuBaoPay->setPrivateKey('test');
        $shanFuBaoPay->setOptions($this->returnResult);
        $shanFuBaoPay->verifyOrderPayment($entry);

        $this->assertEquals('SUCCESS', $shanFuBaoPay->getMsg());
    }
}
