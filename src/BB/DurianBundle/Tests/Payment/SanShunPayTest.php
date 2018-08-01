<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Payment\SanShunPay;
use BB\DurianBundle\Tests\DurianTestCase;

class SanShunPayTest extends DurianTestCase
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
            'orderId' => '201806040000046411',
            'orderCreateDate' => '2018-06-04 21:09:15',
            'paymentVendorId' => '1098',
            'notify_url' => 'http://www.seafood.help/',
            'amount' => '1',
        ];

        $this->returnResult = [
            'memberid' => '9527',
            'orderid' => '201806040000046411',
            'transaction_id' => '201806040010376780021101672',
            'amount' => '1.00',
            'datetime' => '20180604211054',
            'returncode' => '00',
            'sign' => '05D50FF0B15D878EC2EFB2AB5EDCD49B',
            'attach' => '',
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

        $sanShunPay = new SanShunPay();
        $sanShunPay->getVerifyData();
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

        $sanShunPay = new SanShunPay();
        $sanShunPay->setPrivateKey('test');
        $sanShunPay->getVerifyData();
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

        $sanShunPay = new SanShunPay();
        $sanShunPay->setPrivateKey('test');
        $sanShunPay->setOptions($this->option);
        $sanShunPay->getVerifyData();
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $sanShunPay = new SanShunPay();
        $sanShunPay->setPrivateKey('test');
        $sanShunPay->setOptions($this->option);
        $data = $sanShunPay->getVerifyData();

        $this->assertEquals('9527', $data['pay_memberid']);
        $this->assertEquals('201806040000046411', $data['pay_orderid']);
        $this->assertEquals('2018-06-04 21:09:15', $data['pay_applydate']);
        $this->assertEquals('904', $data['pay_bankcode']);
        $this->assertEquals('http://www.seafood.help/', $data['pay_notifyurl']);
        $this->assertEquals('http://www.seafood.help/', $data['pay_callbackurl']);
        $this->assertEquals('1.00', $data['pay_amount']);
        $this->assertEquals('4F7FBC296205ACF0594BB82A509A4FFD', $data['pay_md5sign']);
        $this->assertEquals('201806040000046411', $data['pay_productname']);
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

        $sanShunPay = new SanShunPay();
        $sanShunPay->verifyOrderPayment([]);
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

        $sanShunPay = new SanShunPay();
        $sanShunPay->setPrivateKey('test');
        $sanShunPay->verifyOrderPayment([]);
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

        $sanShunPay = new SanShunPay();
        $sanShunPay->setPrivateKey('test');
        $sanShunPay->setOptions($this->returnResult);
        $sanShunPay->verifyOrderPayment([]);
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

        $this->returnResult['sign'] = '813EE50AF313AC9EEA40AA54D66B463E';

        $sanShunPay = new SanShunPay();
        $sanShunPay->setPrivateKey('test');
        $sanShunPay->setOptions($this->returnResult);
        $sanShunPay->verifyOrderPayment([]);
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

        $this->returnResult['returncode'] = '01';
        $this->returnResult['sign'] = 'E26047771C2B4D60BF3D6C28D8A24BFC';

        $sanShunPay = new SanShunPay();
        $sanShunPay->setPrivateKey('test');
        $sanShunPay->setOptions($this->returnResult);
        $sanShunPay->verifyOrderPayment([]);
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

        $entry = ['id' => '9453'];

        $sanShunPay = new SanShunPay();
        $sanShunPay->setPrivateKey('test');
        $sanShunPay->setOptions($this->returnResult);
        $sanShunPay->verifyOrderPayment($entry);
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
            'id' => '201806040000046411',
            'amount' => '123',
        ];

        $sanShunPay = new SanShunPay();
        $sanShunPay->setPrivateKey('test');
        $sanShunPay->setOptions($this->returnResult);
        $sanShunPay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果
     */
    public function testReturn()
    {
        $entry = [
            'id' => '201806040000046411',
            'amount' => '1',
        ];

        $sanShunPay = new SanShunPay();
        $sanShunPay->setPrivateKey('test');
        $sanShunPay->setOptions($this->returnResult);
        $sanShunPay->verifyOrderPayment($entry);

        $this->assertEquals('OK', $sanShunPay->getMsg());
    }
}
