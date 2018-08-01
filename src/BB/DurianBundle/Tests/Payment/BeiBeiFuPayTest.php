<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\BeiBeiFuPay;

class BeiBeiFuPayTest extends DurianTestCase
{
    /**
     * 測試支付加密時沒有帶入privateKey的情況
     */
    public function testPayEncodeWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $beiBeiFuPay = new BeiBeiFuPay();
        $beiBeiFuPay->getVerifyData();
    }

    /**
     * 測試支付加密時未指定支付參數
     */
    public function testPayEncodeWithNoPayParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No pay parameter specified',
            180145
        );

        $sourceData = ['number' => ''];

        $beiBeiFuPay = new BeiBeiFuPay();
        $beiBeiFuPay->setPrivateKey('1234');
        $beiBeiFuPay->setOptions($sourceData);
        $beiBeiFuPay->getVerifyData();
    }

    /**
     * 測試支付加密時代入支付平台不支援的銀行
     */
    public function testPayEncodeUnsupportedPaymentVendor()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'PaymentVendor is not supported by PaymentGateway',
            180066
        );

        $sourceData = [
            'number' => '1635',
            'paymentVendorId' => '999',
            'amount' => '0.01',
            'orderId' => '201802060000004111',
            'notify_url' => 'http://pay.my/pay/return.php',
        ];

        $beiBeiFuPay = new BeiBeiFuPay();
        $beiBeiFuPay->setPrivateKey('1234');
        $beiBeiFuPay->setOptions($sourceData);
        $beiBeiFuPay->getVerifyData();
    }

    /**
     * 測試支付加密
     */
    public function testPayEncode()
    {
        $sourceData = [
            'number' => '1635',
            'paymentVendorId' => '1090',
            'amount' => '0.01',
            'orderId' => '201802060000004111',
            'notify_url' => 'http://pay.my/pay/return.php',
        ];

        $beiBeiFuPay = new BeiBeiFuPay();
        $beiBeiFuPay->setPrivateKey('1234');
        $beiBeiFuPay->setOptions($sourceData);
        $encodeData = $beiBeiFuPay->getVerifyData();

        $this->assertEquals('GET', $beiBeiFuPay->getPayMethod());
        $this->assertEquals('1635', $encodeData['parter']);
        $this->assertEquals('1004', $encodeData['type']);
        $this->assertEquals('0.01', $encodeData['value']);
        $this->assertEquals('201802060000004111', $encodeData['orderid']);
        $this->assertEquals('http://pay.my/pay/return.php', $encodeData['callbackurl']);
        $this->assertEquals('074e355e9af68f018566c52e47e005ce', $encodeData['sign']);
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

        $beiBeiFuPay = new BeiBeiFuPay();
        $beiBeiFuPay->verifyOrderPayment([]);
    }

    /**
     * 測試解密驗證支付平台未指定返回參數
     */
    public function testVerifyWithNoReturnParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $beiBeiFuPay = new BeiBeiFuPay();
        $beiBeiFuPay->setPrivateKey('1234');
        $beiBeiFuPay->verifyOrderPayment([]);
    }

    /**
     * 測試解密驗證支付平台缺少回傳sign(加密簽名)
     */
    public function testVerifyWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $sourceData = [
            'orderid' => '201802060000004111',
            'opstate' => '0',
            'ovalue' => '0.01',
            'systime' => '2018/02/06 13:59:54',
            'sysorderid' => '1802061359179990684',
            'completiontime' => '2018/02/06 13:59:54',
            'attach' => '',
            'msg' => '',
        ];

        $beiBeiFuPay = new BeiBeiFuPay();
        $beiBeiFuPay->setPrivateKey('1234');
        $beiBeiFuPay->setOptions($sourceData);
        $beiBeiFuPay->verifyOrderPayment([]);
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
            'orderid' => '201802060000004111',
            'opstate' => '0',
            'ovalue' => '0.01',
            'systime' => '2018/02/06 13:59:54',
            'sysorderid' => '1802061359179990684',
            'completiontime' => '2018/02/06 13:59:54',
            'attach' => '',
            'msg' => '',
            'sign' => '2fc85b6bf8c2056c62c2d5d9b06c3acf',
        ];

        $beiBeiFuPay = new BeiBeiFuPay();
        $beiBeiFuPay->setPrivateKey('1234');
        $beiBeiFuPay->setOptions($sourceData);
        $beiBeiFuPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時取得無效的支付參數
     */
    public function testReturnInvalidPayParameters()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Invalid pay parameters',
            180129
        );

        $sourceData = [
            'orderid' => '201802060000004111',
            'opstate' => '-1',
            'ovalue' => '0.01',
            'systime' => '2018/02/06 13:59:54',
            'sysorderid' => '1802061359179990684',
            'completiontime' => '2018/02/06 13:59:54',
            'attach' => '',
            'msg' => '',
            'sign' => 'dc4075f233d7abcab76ffb2ea003fe4c',
        ];

        $beiBeiFuPay = new BeiBeiFuPay();
        $beiBeiFuPay->setPrivateKey('1234');
        $beiBeiFuPay->setOptions($sourceData);
        $beiBeiFuPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回結果為支付平台驗證簽名錯誤
     */
    public function testReturnPaymentGatewaySignatureVerificationFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'PaymentGateway error, Merchant sign error',
            180127
        );

        $sourceData = [
            'orderid' => '201802060000004111',
            'opstate' => '-2',
            'ovalue' => '0.01',
            'systime' => '2018/02/06 13:59:54',
            'sysorderid' => '1802061359179990684',
            'completiontime' => '2018/02/06 13:59:54',
            'attach' => '',
            'msg' => '',
            'sign' => 'fce9a508f1166542fc03fe24579d5c6b',
        ];

        $beiBeiFuPay = new BeiBeiFuPay();
        $beiBeiFuPay->setPrivateKey('1234');
        $beiBeiFuPay->setOptions($sourceData);
        $beiBeiFuPay->verifyOrderPayment([]);
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
            'orderid' => '201802060000004111',
            'opstate' => '-5',
            'ovalue' => '0.01',
            'systime' => '2018/02/06 13:59:54',
            'sysorderid' => '1802061359179990684',
            'completiontime' => '2018/02/06 13:59:54',
            'attach' => '',
            'msg' => '',
            'sign' => '36a4a0a01e2617e2356ae07c64bcd0cf',
        ];

        $beiBeiFuPay = new BeiBeiFuPay();
        $beiBeiFuPay->setPrivateKey('1234');
        $beiBeiFuPay->setOptions($sourceData);
        $beiBeiFuPay->verifyOrderPayment([]);
    }

    /**
     * 測試線上支付單號不正確的情況
     */
    public function testOrderIdError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $sourceData = [
            'orderid' => '201802060000004111',
            'opstate' => '0',
            'ovalue' => '0.01',
            'systime' => '2018/02/06 13:59:54',
            'sysorderid' => '1802061359179990684',
            'completiontime' => '2018/02/06 13:59:54',
            'attach' => '',
            'msg' => '',
            'sign' => '248d2175302e219158ffc4c13a25fc5f',
        ];

        $entry = [
            'id' => '201802060000004112',
            'amount' => '0.01',
        ];

        $beiBeiFuPay = new BeiBeiFuPay();
        $beiBeiFuPay->setPrivateKey('1234');
        $beiBeiFuPay->setOptions($sourceData);
        $beiBeiFuPay->verifyOrderPayment($entry);
    }

    /**
     * 測試線上支付金額不正確的情況
     */
    public function testOrderAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $sourceData = [
            'orderid' => '201802060000004111',
            'opstate' => '0',
            'ovalue' => '0.01',
            'systime' => '2018/02/06 13:59:54',
            'sysorderid' => '1802061359179990684',
            'completiontime' => '2018/02/06 13:59:54',
            'attach' => '',
            'msg' => '',
            'sign' => '248d2175302e219158ffc4c13a25fc5f',
        ];

        $entry = [
            'id' => '201802060000004111',
            'amount' => '1',
        ];

        $beiBeiFuPay = new BeiBeiFuPay();
        $beiBeiFuPay->setPrivateKey('1234');
        $beiBeiFuPay->setOptions($sourceData);
        $beiBeiFuPay->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testPaySuccess()
    {
        $sourceData = [
            'orderid' => '201802060000004111',
            'opstate' => '0',
            'ovalue' => '0.01',
            'systime' => '2018/02/06 13:59:54',
            'sysorderid' => '1802061359179990684',
            'completiontime' => '2018/02/06 13:59:54',
            'attach' => '',
            'msg' => '',
            'sign' => '248d2175302e219158ffc4c13a25fc5f',
        ];

        $entry = [
            'id' => '201802060000004111',
            'amount' => '0.01',
        ];

        $beiBeiFuPay = new BeiBeiFuPay();
        $beiBeiFuPay->setPrivateKey('1234');
        $beiBeiFuPay->setOptions($sourceData);
        $beiBeiFuPay->verifyOrderPayment($entry);

        $this->assertEquals('opstate=0', $beiBeiFuPay->getMsg());
    }
}
