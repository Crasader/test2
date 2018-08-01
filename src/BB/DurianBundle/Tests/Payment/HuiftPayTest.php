<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\HuiftPay;

class HuiftPayTest extends DurianTestCase
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
            'number' => '628900',
            'orderId' => '201805310000005308',
            'notify_url' => 'http://pay.my/pay/pay.php',
            'amount' => '10',
            'paymentVendorId' => '1092',
        ];

        $this->returnResult = [
            'orderNo' => '201805310000005308',
            'transactionNo' => '101805310003123333',
            'amount' => '10.00',
            'sign' => '43a7be9140eb812549542d821d2f233b',
            'extra' => '',
            'merchantNo' => '628900',
            'payStatus' => '1',
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

        $huiftPay = new HuiftPay();
        $huiftPay->getVerifyData();
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

        $huiftPay = new HuiftPay();
        $huiftPay->setPrivateKey('test');
        $huiftPay->setOptions([]);
        $huiftPay->getVerifyData();
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

        $huiftPay = new HuiftPay();
        $huiftPay->setPrivateKey('test');
        $huiftPay->setOptions($this->option);
        $huiftPay->getVerifyData();
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $huiftPay = new HuiftPay();
        $huiftPay->setPrivateKey('test');
        $huiftPay->setOptions($this->option);
        $encodeData = $huiftPay->getVerifyData();

        $this->assertEquals('10.0', $encodeData['amount']);
        $this->assertEquals('628900', $encodeData['merchantNo']);
        $this->assertEquals('201805310000005308', $encodeData['orderNo']);
        $this->assertEquals('0deeb64004b923797c473cfa852ff32b', $encodeData['sign']);
        $this->assertEquals('ALISCAN', $encodeData['bank']);
        $this->assertEquals('201805310000005308', $encodeData['name']);
        $this->assertEquals('1', $encodeData['count']);
        $this->assertEquals('http://pay.my/pay/pay.php', $encodeData['returnUrl']);
        $this->assertEquals('http://pay.my/pay/pay.php', $encodeData['notifyUrl']);
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

        $huiftPay = new HuiftPay();
        $huiftPay->verifyOrderPayment([]);
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

        $huiftPay = new HuiftPay();
        $huiftPay->setPrivateKey('test');
        $huiftPay->verifyOrderPayment([]);
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

        $huiftPay = new HuiftPay();
        $huiftPay->setPrivateKey('test');
        $huiftPay->setOptions($this->returnResult);
        $huiftPay->verifyOrderPayment([]);
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

        $this->returnResult['sign'] = '175f7453f65c34fc61e7995b82de55ea';

        $huiftPay = new HuiftPay();
        $huiftPay->setPrivateKey('test');
        $huiftPay->setOptions($this->returnResult);
        $huiftPay->verifyOrderPayment([]);
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

        $this->returnResult['sign'] = '43a7be9140eb812549542d821d2f233b';
        $this->returnResult['payStatus'] = '-1';

        $huiftPay = new HuiftPay();
        $huiftPay->setPrivateKey('test');
        $huiftPay->setOptions($this->returnResult);
        $huiftPay->verifyOrderPayment([]);
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

        $entry = ['id' => '201805310000005309'];

        $huiftPay = new HuiftPay();
        $huiftPay->setPrivateKey('test');
        $huiftPay->setOptions($this->returnResult);
        $huiftPay->verifyOrderPayment($entry);
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
            'id' => '201805310000005308',
            'amount' => '100',
        ];

        $huiftPay = new HuiftPay();
        $huiftPay->setPrivateKey('test');
        $huiftPay->setOptions($this->returnResult);
        $huiftPay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回時支付成功
     */
    public function testReturnResultSuccess()
    {
        $entry = [
            'id' => '201805310000005308',
            'amount' => '10',
        ];

        $huiftPay = new HuiftPay();
        $huiftPay->setPrivateKey('test');
        $huiftPay->setOptions($this->returnResult);
        $huiftPay->verifyOrderPayment($entry);

        $this->assertEquals('SUCCESS', $huiftPay->getMsg());
    }
}