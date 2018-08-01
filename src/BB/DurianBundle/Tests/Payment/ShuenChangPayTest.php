<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\ShuenChangPay;

class ShuenChangPayTest extends DurianTestCase
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

        $shuenChangPay = new ShuenChangPay();
        $shuenChangPay->getVerifyData();
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

        $sourceData = ['number' => ''];

        $shuenChangPay = new ShuenChangPay();
        $shuenChangPay->setPrivateKey('test');
        $shuenChangPay->setOptions($sourceData);
        $shuenChangPay->getVerifyData();
    }

    /**
     * 測試支付時代入支付平台不支援的銀行
     */
    public function testPayUnsupportedPaymentVendor()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'PaymentVendor is not supported by PaymentGateway',
            180066
        );

        $sourceData = [
            'number' => '79856022',
            'paymentVendorId' => '9999',
            'amount' => '0.01',
            'orderId' => '201711070000002110',
            'username' => 'php1test',
            'notify_url' => 'http://pay.my/pay/return.php',
        ];

        $shuenChangPay = new ShuenChangPay();
        $shuenChangPay->setPrivateKey('test');
        $shuenChangPay->setOptions($sourceData);
        $shuenChangPay->getVerifyData();
    }

    /**
     * 測試支付成功
     */
    public function testPaySuccess()
    {
        $sourceData = [
            'number' => '79856022',
            'paymentVendorId' => '1092',
            'amount' => '0.01',
            'orderId' => '201711070000002110',
            'username' => 'php1test',
            'notify_url' => 'http://pay.my/pay/return.php',
        ];

        $shuenChangPay = new ShuenChangPay();
        $shuenChangPay->setPrivateKey('test');
        $shuenChangPay->setOptions($sourceData);
        $requestData = $shuenChangPay->getVerifyData();

        $this->assertEquals('79856022', $requestData['partner']);
        $this->assertEquals('201711070000002110', $requestData['trade_no']);
        $this->assertEquals('0.01', $requestData['total_fee']);
        $this->assertEquals('php1test', $requestData['remark']);
        $this->assertEquals('http://pay.my/pay/return.php', $requestData['ReturnUrl']);
        $this->assertEquals('http://pay.my/pay/return.php', $requestData['NotifyUrl']);
        $this->assertEquals('601', $requestData['payType']);
        $this->assertEquals('71fec757928fa6dabcf02c37aafb03cd', $requestData['sign']);
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

        $shuenChangPay = new ShuenChangPay();
        $shuenChangPay->verifyOrderPayment([]);
    }

    /**
     *測試返回時未指定返回參數
     */
    public function testReturnNoReturnParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $shuenChangPay = new ShuenChangPay();
        $shuenChangPay->setPrivateKey('test');
        $shuenChangPay->verifyOrderPayment([]);
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

        $sourceData = [
            'trade_no' => '201711070000002110',
            'total_fee' => '0.01000',
            'status' => '1',
            'transaction_id' => '79856022201711070000002110',
            'remark' => 'php1test',
        ];

        $entry = ['merchant_number' => '79856022'];

        $shuenChangPay = new ShuenChangPay();
        $shuenChangPay->setPrivateKey('test');
        $shuenChangPay->setOptions($sourceData);
        $shuenChangPay->verifyOrderPayment($entry);
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
            'trade_no' => '201711070000002110',
            'total_fee' => '0.01000',
            'status' => '1',
            'transaction_id' => '79856022201711070000002110',
            'remark' => 'php1test',
            'sign' => '0f5bf7a1383f96d13083ab1704403b0a',
        ];

        $entry = ['merchant_number' => '79856022'];

        $shuenChangPay = new ShuenChangPay();
        $shuenChangPay->setPrivateKey('test');
        $shuenChangPay->setOptions($sourceData);
        $shuenChangPay->verifyOrderPayment($entry);
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
            'trade_no' => '201711070000002110',
            'total_fee' => '0.01000',
            'status' => '0',
            'transaction_id' => '79856022201711070000002110',
            'remark' => 'php1test',
            'sign' => '6d7a50fcb03d1643d671581108f6294a',
        ];

        $entry = ['merchant_number' => '79856022'];

        $shuenChangPay = new ShuenChangPay();
        $shuenChangPay->setPrivateKey('test');
        $shuenChangPay->setOptions($sourceData);
        $shuenChangPay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果訂單號錯誤
     */
    public function testReturnOrderIdError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $sourceData = [
            'trade_no' => '201711070000002110',
            'total_fee' => '0.01000',
            'status' => '1',
            'transaction_id' => '79856022201711070000002110',
            'remark' => 'php1test',
            'sign' => '66e2b965ce5dec18078209841758eb66',
        ];

        $entry = [
            'merchant_number' => '79856022',
            'id' => '201711070000002111',
            'amount' => '0.01',
        ];

        $shuenChangPay = new ShuenChangPay();
        $shuenChangPay->setPrivateKey('test');
        $shuenChangPay->setOptions($sourceData);
        $shuenChangPay->verifyOrderPayment($entry);
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

        $sourceData = [
            'trade_no' => '201711070000002110',
            'total_fee' => '0.01000',
            'status' => '1',
            'transaction_id' => '79856022201711070000002110',
            'remark' => 'php1test',
            'sign' => '66e2b965ce5dec18078209841758eb66',
        ];

        $entry = [
            'merchant_number' => '79856022',
            'id' => '201711070000002110',
            'amount' => '0.02',
        ];

        $shuenChangPay = new ShuenChangPay();
        $shuenChangPay->setPrivateKey('test');
        $shuenChangPay->setOptions($sourceData);
        $shuenChangPay->verifyOrderPayment($entry);
    }

    /**
     * 測試支付認證成功
     */
    public function testReturnOrder()
    {
        $sourceData = [
            'trade_no' => '201711070000002110',
            'total_fee' => '0.01000',
            'status' => '1',
            'transaction_id' => '79856022201711070000002110',
            'remark' => 'php1test',
            'sign' => '66e2b965ce5dec18078209841758eb66',
        ];

        $entry = [
            'merchant_number' => '79856022',
            'id' => '201711070000002110',
            'amount' => '0.01',
        ];

        $shuenChangPay = new ShuenChangPay();
        $shuenChangPay->setPrivateKey('test');
        $shuenChangPay->setOptions($sourceData);
        $shuenChangPay->verifyOrderPayment($entry);

        $this->assertEquals('success', $shuenChangPay->getMsg());
    }
}
