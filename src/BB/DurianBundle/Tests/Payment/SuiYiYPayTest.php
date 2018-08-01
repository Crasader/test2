<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Payment\SuiYiYPay;
use BB\DurianBundle\Tests\DurianTestCase;

class SuiYiYPayTest extends DurianTestCase
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
            'paymentVendorId' => '1',
            'amount' => '1',
            'orderId' => '201807160000046634',
            'notify_url' => 'http://www.seafood.help/',
        ];

        $this->returnResult = [
            'orderid' => '201807160000046634',
            'opstate' => '0',
            'ovalue' => '1.00',
            'systime' => '2018/07/16 20:44:27',
            'sysorderid' => '18071620440543815242',
            'completiontime' => '2018/07/16 20:44:27',
            'attach' => '',
            'msg' => '',
            'type' => '967',
            'sign' => '9261e5be0b5d848088c16f9ca8d967e1',
            'RSA_sign' => 'HAW/uy0oebG/udh1YXoS64RMvSfHLlNkqkrdZWY_2NkLb35DvjTfujfivrgX33tduPE8N222oVmZyl6xf8tDT4E',
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

        $suiYiYPay = new SuiYiYPay();
        $suiYiYPay->getVerifyData();
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

        $suiYiYPay = new SuiYiYPay();
        $suiYiYPay->setPrivateKey('test');
        $suiYiYPay->getVerifyData();
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

        $suiYiYPay = new SuiYiYPay();
        $suiYiYPay->setPrivateKey('test');
        $suiYiYPay->setOptions($this->option);
        $suiYiYPay->getVerifyData();
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $suiYiYPay = new SuiYiYPay();
        $suiYiYPay->setPrivateKey('test');
        $suiYiYPay->setOptions($this->option);
        $data = $suiYiYPay->getVerifyData();

        $this->assertEquals('9527', $data['parter']);
        $this->assertEquals('967', $data['type']);
        $this->assertEquals('1.00', $data['value']);
        $this->assertEquals('201807160000046634', $data['orderid']);
        $this->assertEquals('http://www.seafood.help/', $data['callbackurl']);
        $this->assertEquals('', $data['hrefbackurl']);
        $this->assertEquals('', $data['attach']);
        $this->assertEquals('a9103ab7c034ac7f84200f99f824ca77', $data['sign']);
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

        $suiYiYPay = new SuiYiYPay();
        $suiYiYPay->verifyOrderPayment([]);
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

        $suiYiYPay = new SuiYiYPay();
        $suiYiYPay->setPrivateKey('test');
        $suiYiYPay->verifyOrderPayment([]);
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

        $suiYiYPay = new SuiYiYPay();
        $suiYiYPay->setPrivateKey('test');
        $suiYiYPay->setOptions($this->returnResult);
        $suiYiYPay->verifyOrderPayment([]);
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

        $this->returnResult['sign'] = '7c0c399ad2c2a412910455e845686e26';

        $suiYiYPay = new SuiYiYPay();
        $suiYiYPay->setPrivateKey('test');
        $suiYiYPay->setOptions($this->returnResult);
        $suiYiYPay->verifyOrderPayment([]);
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

        $this->returnResult['opstate'] = '-1';
        $this->returnResult['sign'] = 'e109512222069ebca37014980e519512';

        $suiYiYPay = new SuiYiYPay();
        $suiYiYPay->setPrivateKey('test');
        $suiYiYPay->setOptions($this->returnResult);
        $suiYiYPay->verifyOrderPayment([]);
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

        $suiYiYPay = new SuiYiYPay();
        $suiYiYPay->setPrivateKey('test');
        $suiYiYPay->setOptions($this->returnResult);
        $suiYiYPay->verifyOrderPayment($entry);
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
            'id' => '201807160000046634',
            'amount' => '123',
        ];

        $suiYiYPay = new SuiYiYPay();
        $suiYiYPay->setPrivateKey('test');
        $suiYiYPay->setOptions($this->returnResult);
        $suiYiYPay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果
     */
    public function testReturn()
    {
        $entry = [
            'id' => '201807160000046634',
            'amount' => '1',
        ];

        $suiYiYPay = new SuiYiYPay();
        $suiYiYPay->setPrivateKey('test');
        $suiYiYPay->setOptions($this->returnResult);
        $suiYiYPay->verifyOrderPayment($entry);

        $this->assertEquals('opstate=0', $suiYiYPay->getMsg());
    }
}
