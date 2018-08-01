<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\XgxPay;

class XgxPayTest extends DurianTestCase
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
            'notify_url' => 'http://www.seafood.help/',
            'ip' => '127.0.0.1',
            'orderId' => '201803212100009527',
            'amount' => '3.00',
            'paymentVendorId' => '1',
        ];

        $this->returnResult = [
            'merchant_id' => '9527',
            'source_order_id' => '201803212100009527',
            'order_amount' => '3.000',
            'goods_name' => '201803212100009527',
            'payTime' => '2018-03-26 14:28:47',
            'status' => '1',
            'sign' => 'f80211bc34263350e435b40b2194b8ce',
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

        $xgxPay = new XgxPay();
        $xgxPay->getVerifyData();
    }

    /**
     * 測試支付時沒指定支付參數
     */
    public function testPayWithNoPayParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No pay parameter specified',
            180145
        );

        $xgxPay = new XgxPay();
        $xgxPay->setPrivateKey('test');
        $xgxPay->getVerifyData();
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

        $this->option['paymentVendorId'] = '999';

        $xgxPay = new XgxPay();
        $xgxPay->setPrivateKey('test');
        $xgxPay->setOptions($this->option);
        $xgxPay->getVerifyData();
    }

    /**
     * 測試銀聯在線支付
     */
    public function testUnionPay()
    {
        $this->option['paymentVendorId'] = '278';

        $xgxPay = new XgxPay();
        $xgxPay->setPrivateKey('test');
        $xgxPay->setOptions($this->option);
        $data = $xgxPay->getVerifyData();

        $this->assertEquals('9527', $data['merchant_id']);
        $this->assertEquals('10', $data['payment_way']);
        $this->assertEquals('0ecdb55c125b8c809baff1c91e195641', $data['sign']);
        $this->assertEquals('http://www.seafood.help/', $data['return_url']);
        $this->assertEquals('127.0.0.1', $data['client_ip']);
        $this->assertEquals('201803212100009527', $data['goods_name']);
        $this->assertEquals('201803212100009527', $data['source_order_id']);
        $this->assertEquals('http://www.seafood.help/', $data['notify_url']);
        $this->assertEquals('3.00', $data['order_amount']);
        $this->assertEquals('', $data['bank_code']);
    }

    /**
     * 測試二維支付
     */
    public function testQrcodePay()
    {
        $this->option['paymentVendorId'] = '1092';

        $xgxPay = new XgxPay();
        $xgxPay->setPrivateKey('test');
        $xgxPay->setOptions($this->option);
        $data = $xgxPay->getVerifyData();

        $this->assertEquals('9527', $data['merchant_id']);
        $this->assertEquals('43', $data['payment_way']);
        $this->assertEquals('b678ccc41d8d6503426f05c39aa7a4d3', $data['sign']);
        $this->assertEquals('http://www.seafood.help/', $data['return_url']);
        $this->assertEquals('127.0.0.1', $data['client_ip']);
        $this->assertEquals('201803212100009527', $data['goods_name']);
        $this->assertEquals('201803212100009527', $data['source_order_id']);
        $this->assertEquals('http://www.seafood.help/', $data['notify_url']);
        $this->assertEquals('3.00', $data['order_amount']);
        $this->assertEquals('', $data['bank_code']);
    }

    /**
     * 測試手機支付
     */
    public function testPhonePay()
    {
        $this->option['paymentVendorId'] = '1088';

        $xgxPay = new XgxPay();
        $xgxPay->setPrivateKey('test');
        $xgxPay->setOptions($this->option);
        $data = $xgxPay->getVerifyData();

        $this->assertEquals('9527', $data['merchant_id']);
        $this->assertEquals('21', $data['payment_way']);
        $this->assertEquals('c1aa3eb00a7e9cf08d65889381e6fd6e', $data['sign']);
        $this->assertEquals('http://www.seafood.help/', $data['return_url']);
        $this->assertEquals('127.0.0.1', $data['client_ip']);
        $this->assertEquals('201803212100009527', $data['goods_name']);
        $this->assertEquals('201803212100009527', $data['source_order_id']);
        $this->assertEquals('http://www.seafood.help/', $data['notify_url']);
        $this->assertEquals('3.00', $data['order_amount']);
        $this->assertEquals('', $data['bank_code']);
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $xgxPay = new XgxPay();
        $xgxPay->setPrivateKey('test');
        $xgxPay->setOptions($this->option);
        $data = $xgxPay->getVerifyData();

        $this->assertEquals('9527', $data['merchant_id']);
        $this->assertEquals('3', $data['payment_way']);
        $this->assertEquals('91a27b72df8540adc3e6d48e410756a5', $data['sign']);
        $this->assertEquals('http://www.seafood.help/', $data['return_url']);
        $this->assertEquals('127.0.0.1', $data['client_ip']);
        $this->assertEquals('201803212100009527', $data['goods_name']);
        $this->assertEquals('201803212100009527', $data['source_order_id']);
        $this->assertEquals('http://www.seafood.help/', $data['notify_url']);
        $this->assertEquals('3.00', $data['order_amount']);
        $this->assertEquals('ICBC', $data['bank_code']);
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

        $xgxPay = new XgxPay();
        $xgxPay->verifyOrderPayment([]);
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

        $xgxPay = new XgxPay();
        $xgxPay->setPrivateKey('test');
        $xgxPay->verifyOrderPayment([]);
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

        unset($this->returnResult['sign']);

        $xgxPay = new XgxPay();
        $xgxPay->setPrivateKey('test');
        $xgxPay->setOptions($this->returnResult);
        $xgxPay->verifyOrderPayment([]);
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

        $this->returnResult['sign'] = 'e0e68494ce8e921762a893a04c47820b';

        $xgxPay = new XgxPay();
        $xgxPay->setPrivateKey('test');
        $xgxPay->setOptions($this->returnResult);
        $xgxPay->verifyOrderPayment([]);
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

        $this->returnResult['status'] = 2;
        $this->returnResult['order_code'] = 201804170000046031;
        $this->returnResult['sign'] = '5b7f9c6534bac4e13f21157b696bc6dc';

        $xgxPay = new XgxPay();
        $xgxPay->setPrivateKey('test');
        $xgxPay->setOptions($this->returnResult);
        $xgxPay->verifyOrderPayment([]);
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

        $entry = ['id' => '201503220000000555'];

        $xgxPay = new XgxPay();
        $xgxPay->setPrivateKey('test');
        $xgxPay->setOptions($this->returnResult);
        $xgxPay->verifyOrderPayment($entry);
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

        $entry = [
            'id' => '201803212100009527',
            'amount' => '15.00',
        ];

        $xgxPay = new XgxPay();
        $xgxPay->setPrivateKey('test');
        $xgxPay->setOptions($this->returnResult);
        $xgxPay->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testReturnOrder()
    {
        $entry = [
            'id' => '201803212100009527',
            'amount' => '3.00',
        ];

        $xgxPay = new XgxPay();
        $xgxPay->setPrivateKey('test');
        $xgxPay->setOptions($this->returnResult);
        $xgxPay->verifyOrderPayment($entry);

        $this->assertEquals('ok', $xgxPay->getMsg());
    }
}
