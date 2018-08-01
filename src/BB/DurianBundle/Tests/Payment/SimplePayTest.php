<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\SimplePay;

class SimplePayTest extends DurianTestCase
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

        $simplePay = new SimplePay();
        $simplePay->getVerifyData();
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

        $simplePay = new SimplePay();
        $simplePay->setPrivateKey('test');
        $simplePay->setOptions($sourceData);
        $simplePay->getVerifyData();
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
            'number' => '52071603',
            'paymentVendorId' => '9999',
            'amount' => '0.01',
            'orderId' => '201710170000001597',
            'username' => 'php1test',
            'notify_url' => 'http://pay.my/pay/return.php'
        ];

        $simplePay = new SimplePay();
        $simplePay->setPrivateKey('test');
        $simplePay->setOptions($sourceData);
        $simplePay->getVerifyData();
    }

    /**
     * 測試支付成功(支付寶)
     */
    public function testPaySuccess()
    {
        $sourceData = [
            'number' => '52071603',
            'paymentVendorId' => '1092',
            'amount' => '0.01',
            'orderId' => '201710170000001597',
            'username' => 'php1test',
            'notify_url' => 'http://pay.my/pay/return.php'
        ];

        $simplePay = new SimplePay();
        $simplePay->setPrivateKey('test');
        $simplePay->setOptions($sourceData);
        $requestData = $simplePay->getVerifyData();

        $this->assertEquals('52071603', $requestData['partner']);
        $this->assertEquals('201710170000001597', $requestData['trade_no']);
        $this->assertEquals('0.01', $requestData['total_fee']);
        $this->assertEquals('php1test', $requestData['remark']);
        $this->assertEquals('http://pay.my/pay/return.php', $requestData['ReturnUrl']);
        $this->assertEquals('http://pay.my/pay/return.php', $requestData['NotifyUrl']);
        $this->assertEquals('601', $requestData['payType']);
        $this->assertEquals('dbf3b5328590c63ca0b3236f9d8250b6', $requestData['sign']);
    }

    /**
     * 測試支付成功(微信)
     */
    public function testWeiXinSuccess()
    {
        $sourceData = [
            'number' => '52071603',
            'paymentVendorId' => '1090',
            'amount' => '0.01',
            'orderId' => '201710170000001597',
            'username' => 'php1test',
            'notify_url' => 'http://pay.my/pay/return.php',
            'verify_url' => 'https://pay.kuaidiana.net/pay/index',
        ];

        $simplePay = new SimplePay();
        $simplePay->setPrivateKey('test');
        $simplePay->setOptions($sourceData);
        $requestData = $simplePay->getVerifyData();

        $this->assertEquals('52071603', $requestData['partner']);
        $this->assertEquals('201710170000001597', $requestData['trade_no']);
        $this->assertEquals('0.01', $requestData['total_fee']);
        $this->assertEquals('php1test', $requestData['remark']);
        $this->assertEquals('http://pay.my/pay/return.php', $requestData['ReturnUrl']);
        $this->assertEquals('http://pay.my/pay/return.php', $requestData['NotifyUrl']);
        $this->assertEquals('605', $requestData['payType']);
        $this->assertEquals('https://pay.kuaidiana.net/pay/index', $requestData['act_url']);
        $this->assertEquals('78286e588dd1e5dee9b2cafe905ca7a0', $requestData['sign']);
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

        $simplePay = new SimplePay();
        $simplePay->verifyOrderPayment([]);
    }

    /**
     *測試返回時未指定返回參數
     */
    public function testReturnWithReturnParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $simplePay = new SimplePay();
        $simplePay->setPrivateKey('test');
        $simplePay->verifyOrderPayment([]);
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
            'trade_no' => '201710170000001597',
            'total_fee' => '0.01000',
            'status' => '1',
            'transaction_id' => '52071603201710170000001597',
            'remark' => 'php1test',
        ];

        $entry = ['merchant_number' => '52071603'];

        $simplePay = new SimplePay();
        $simplePay->setPrivateKey('test');
        $simplePay->setOptions($sourceData);
        $simplePay->verifyOrderPayment($entry);
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
            'trade_no' => '201710170000001597',
            'total_fee' => '0.01000',
            'status' => '1',
            'transaction_id' => '52071603201710170000001597',
            'remark' => 'php1test',
            'sign' => '143d81d596b4ec9e7503bee389d74e92',
        ];

        $entry = ['merchant_number' => '52071603'];

        $simplePay = new SimplePay();
        $simplePay->setPrivateKey('test');
        $simplePay->setOptions($sourceData);
        $simplePay->verifyOrderPayment($entry);
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
            'trade_no' => '201710170000001597',
            'total_fee' => '0.01000',
            'status' => '0',
            'transaction_id' => '52071603201710170000001597',
            'remark' => 'php1test',
            'sign' => '3ba34c343336ff462e3c8393e2411944',
        ];

        $entry = ['merchant_number' => '52071603'];

        $simplePay = new SimplePay();
        $simplePay->setPrivateKey('test');
        $simplePay->setOptions($sourceData);
        $simplePay->verifyOrderPayment($entry);
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
            'trade_no' => '201710170000001597',
            'total_fee' => '0.01000',
            'status' => '1',
            'transaction_id' => '52071603201710170000001597',
            'remark' => 'php1test',
            'sign' => 'f6f9c78e05261afa51fa85e24ac57e2b',
        ];

        $entry = [
            'merchant_number' => '52071603',
            'id' => '201710170000001596',
            'amount' => '0.01',
        ];

        $simplePay = new SimplePay();
        $simplePay->setPrivateKey('test');
        $simplePay->setOptions($sourceData);
        $simplePay->verifyOrderPayment($entry);
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
            'trade_no' => '201710170000001597',
            'total_fee' => '0.01000',
            'status' => '1',
            'transaction_id' => '52071603201710170000001597',
            'remark' => 'php1test',
            'sign' => 'f6f9c78e05261afa51fa85e24ac57e2b',
        ];

        $entry = [
            'merchant_number' => '52071603',
            'id' => '201710170000001597',
            'amount' => '1.00',
        ];

        $simplePay = new SimplePay();
        $simplePay->setPrivateKey('test');
        $simplePay->setOptions($sourceData);
        $simplePay->verifyOrderPayment($entry);
    }

    /**
     * 測試支付認證成功
     */
    public function testReturnOrder()
    {
        $sourceData = [
            'trade_no' => '201710170000001597',
            'total_fee' => '0.01000',
            'status' => '1',
            'transaction_id' => '52071603201710170000001597',
            'remark' => 'php1test',
            'sign' => 'f6f9c78e05261afa51fa85e24ac57e2b',
        ];

        $entry = [
            'merchant_number' => '52071603',
            'id' => '201710170000001597',
            'amount' => '0.01',
        ];

        $simplePay = new SimplePay();
        $simplePay->setPrivateKey('test');
        $simplePay->setOptions($sourceData);
        $simplePay->verifyOrderPayment($entry);

        $this->assertEquals('success', $simplePay->getMsg());
    }
}