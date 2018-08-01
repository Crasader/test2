<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\HuiYin;

class HuiYinTest extends DurianTestCase
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

        $huiYin = new HuiYin();
        $huiYin->getVerifyData();
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

        $huiYin = new HuiYin();
        $huiYin->setPrivateKey('test');
        $huiYin->getVerifyData();
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
            'number' => '1002',
            'paymentVendorId' => '999',
            'amount' => '1.20',
            'orderId' => '201803230000010528',
            'notify_url' => 'http://pay.my/pay/return.php',
        ];

        $huiYin = new HuiYin();
        $huiYin->setPrivateKey('test');
        $huiYin->setOptions($options);
        $huiYin->getVerifyData();
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $options = [
            'number' => '1002',
            'paymentVendorId' => '1',
            'amount' => '1.20',
            'orderId' => '201803230000010528',
            'notify_url' => 'http://pay.my/pay/return.php',
        ];

        $huiYin = new HuiYin();
        $huiYin->setPrivateKey('test');
        $huiYin->setOptions($options);
        $requestData = $huiYin->getVerifyData();

        $this->assertEquals('1002', $requestData['merchantcode']);
        $this->assertEquals('ICBC', $requestData['type']);
        $this->assertEquals('1.20', $requestData['amount']);
        $this->assertEquals('201803230000010528', $requestData['orderid']);
        $this->assertEquals('http://pay.my/pay/return.php', $requestData['notifyurl']);
        $this->assertEquals('5C52BE4A53B0D0E61DE6F32641392D18', $requestData['sign']);
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

        $huiYin = new HuiYin();
        $huiYin->verifyOrderPayment([]);
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

        $huiYin = new HuiYin();
        $huiYin->setPrivateKey('test');
        $huiYin->verifyOrderPayment([]);
    }

    /**
     * 測試返回時缺少加密簽名
     */
    public function testReturnWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $options = [
            'orderid' => '201803230000010528',
            'status' => '0',
            'amount' => 1.20,
            'platformorderid' => '18032313580887130639',
            'paytime' => '2018/03/23 13:58:08',
            'completetime' => '2018/03/23 13:58:47',
            'desc' => '',
            'merchantcode' => '1002',
        ];

        $huiYin = new HuiYin();
        $huiYin->setPrivateKey('test');
        $huiYin->setOptions($options);
        $huiYin->verifyOrderPayment([]);
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
            'orderid' => '201803230000010528',
            'status' => '0',
            'amount' => 1.20,
            'platformorderid' => '18032313580887130639',
            'paytime' => '2018/03/23 13:58:08',
            'completetime' => '2018/03/23 13:58:47',
            'desc' => '',
            'merchantcode' => '1002',
            'sign' => 'test',
        ];

        $huiYin = new HuiYin();
        $huiYin->setPrivateKey('test');
        $huiYin->setOptions($options);
        $huiYin->verifyOrderPayment([]);
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
            'orderid' => '201803230000010528',
            'status' => '1',
            'amount' => 1.20,
            'platformorderid' => '18032313580887130639',
            'paytime' => '2018/03/23 13:58:08',
            'completetime' => '2018/03/23 13:58:47',
            'desc' => '',
            'merchantcode' => '1002',
            'sign' => '1E7DCF52C270B43F98464E00BD369DA9',
        ];

        $huiYin = new HuiYin();
        $huiYin->setPrivateKey('test');
        $huiYin->setOptions($options);
        $huiYin->verifyOrderPayment([]);
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
            'orderid' => '201803230000010528',
            'status' => '0',
            'amount' => 1.20,
            'platformorderid' => '18032313580887130639',
            'paytime' => '2018/03/23 13:58:08',
            'completetime' => '2018/03/23 13:58:47',
            'desc' => '',
            'merchantcode' => '1002',
            'sign' => '2BCFD3AD778120C0FE55AB6F89DAD9EF',
        ];

        $entry = [
            'id' => '201801190000003819',
        ];

        $huiYin = new HuiYin();
        $huiYin->setPrivateKey('test');
        $huiYin->setOptions($options);
        $huiYin->verifyOrderPayment($entry);
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
            'orderid' => '201803230000010528',
            'status' => '0',
            'amount' => 1.20,
            'platformorderid' => '18032313580887130639',
            'paytime' => '2018/03/23 13:58:08',
            'completetime' => '2018/03/23 13:58:47',
            'desc' => '',
            'merchantcode' => '1002',
            'sign' => '2BCFD3AD778120C0FE55AB6F89DAD9EF',
        ];

        $entry = [
            'id' => '201803230000010528',
            'amount' => '2.8',
        ];

        $huiYin = new HuiYin();
        $huiYin->setPrivateKey('test');
        $huiYin->setOptions($options);
        $huiYin->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果
     */
    public function testReturnOrder()
    {
        $options = [
            'orderid' => '201803230000010528',
            'status' => '0',
            'amount' => 1.20,
            'platformorderid' => '18032313580887130639',
            'paytime' => '2018/03/23 13:58:08',
            'completetime' => '2018/03/23 13:58:47',
            'desc' => '',
            'merchantcode' => '1002',
            'sign' => '2BCFD3AD778120C0FE55AB6F89DAD9EF',
        ];

        $entry = [
            'id' => '201803230000010528',
            'amount' => '1.2',
        ];

        $huiYin = new HuiYin();
        $huiYin->setPrivateKey('test');
        $huiYin->setOptions($options);
        $huiYin->verifyOrderPayment($entry);

        $this->assertEquals('success', $huiYin->getMsg());
    }
}
