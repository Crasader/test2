<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\YiYunPay;

class YiYunPayTest extends DurianTestCase
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
            'number' => '18500',
            'orderId' => '201805310000005289',
            'notify_url' => 'http://pay.my/pay/pay.php',
            'amount' => '1',
            'paymentVendorId' => '1092',
        ];

        $this->returnResult = [
            'partner' => '18500',
            'ordernumber' => '201805310000005289',
            'orderstatus' => '1',
            'paymoney' => '1.000',
            'sysnumber' => '180531175915717',
            'attach' => '',
            'sign' => '200c3b456b184156565602b751093ac2',
        ];
    }

    /**
     * 測試支付時沒有私鑰
     */
    public function testPayWithoutKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $yiYunPayy = new YiYunPay();
        $yiYunPayy->getVerifyData();
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

        $yiYunPayy = new YiYunPay();
        $yiYunPayy->setPrivateKey('test');
        $yiYunPayy->setOptions([]);
        $yiYunPayy->getVerifyData();
    }

    /**
     * 測試支付時帶入不支援的銀行
     */
    public function testPayWithUnsupportedPaymentVendor()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'PaymentVendor is not supported by PaymentGateway',
            180066
        );

        $this->option['paymentVendorId'] = '9999';

        $yiYunPayy = new YiYunPay();
        $yiYunPayy->setPrivateKey('test');
        $yiYunPayy->setOptions($this->option);
        $yiYunPayy->getVerifyData();
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $yiYunPayy = new YiYunPay();
        $yiYunPayy->setPrivateKey('test');
        $yiYunPayy->setOptions($this->option);
        $encodeData = $yiYunPayy->getVerifyData();

        $this->assertEquals('3.0', $encodeData['version']);
        $this->assertEquals('yy.online.interface', $encodeData['method']);
        $this->assertEquals('18500', $encodeData['partner']);
        $this->assertEquals('ALIPAY', $encodeData['banktype']);
        $this->assertEquals('1.00', $encodeData['paymoney']);
        $this->assertEquals('201805310000005289', $encodeData['ordernumber']);
        $this->assertEquals('http://pay.my/pay/pay.php', $encodeData['callbackurl']);
        $this->assertEquals('1', $encodeData['isshow']);
        $this->assertEquals('330080a6b61bc209054dc9030c7f1829', $encodeData['sign']);
    }

    /**
     * 測試返回時沒有私鑰
     */
    public function testReturnWithoutKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $yiYunPayy = new YiYunPay();
        $yiYunPayy->verifyOrderPayment([]);
    }

    /**
     * 測試返回時未指定參數
     */
    public function testReturnWithNoParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $yiYunPayy = new YiYunPay();
        $yiYunPayy->setPrivateKey('test');
        $yiYunPayy->verifyOrderPayment([]);
    }

    /**
     * 測試返回時缺少加密簽名
     */
    public function testReturnWithoutSignature()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        unset($this->returnResult['sign']);

        $yiYunPayy = new YiYunPay();
        $yiYunPayy->setPrivateKey('test');
        $yiYunPayy->setOptions($this->returnResult);
        $yiYunPayy->verifyOrderPayment([]);
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

        $this->returnResult['sign'] = '629234e2ae0b18823b714ab9afd28a58';

        $yiYunPayy = new YiYunPay();
        $yiYunPayy->setPrivateKey('test');
        $yiYunPayy->setOptions($this->returnResult);
        $yiYunPayy->verifyOrderPayment([]);
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

        $this->returnResult['sign'] = 'e308669f01b19ee85494c9b938b92e7c';
        $this->returnResult['orderstatus'] = '-1';

        $yiYunPay = new YiYunPay();
        $yiYunPay->setPrivateKey('test');
        $yiYunPay->setOptions($this->returnResult);
        $yiYunPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時單號不正確
     */
    public function testReturnPaymentOrderIdError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $entry = ['id' => '201805310000005288'];

        $yiYunPay = new YiYunPay();
        $yiYunPay->setPrivateKey('test');
        $yiYunPay->setOptions($this->returnResult);
        $yiYunPay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回時金額不正確
     */
    public function testReturnPaymentOrderAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $entry = [
            'id' => '201805310000005289',
            'amount' => '100',
        ];

        $yiYunPay = new YiYunPay();
        $yiYunPay->setPrivateKey('test');
        $yiYunPay->setOptions($this->returnResult);
        $yiYunPay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回時支付成功
     */
    public function testReturnResultSuccess()
    {
        $entry = [
            'id' => '201805310000005289',
            'amount' => '1',
        ];

        $yiYunPay = new YiYunPay();
        $yiYunPay->setPrivateKey('test');
        $yiYunPay->setOptions($this->returnResult);
        $yiYunPay->verifyOrderPayment($entry);

        $this->assertEquals('ok', $yiYunPay->getMsg());
    }
}