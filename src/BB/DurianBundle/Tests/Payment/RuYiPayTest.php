<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\RuYiPay;

class RuYiPayTest extends DurianTestCase
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

        $ruYiPay = new RuYiPay();
        $ruYiPay->getVerifyData();
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

        $ruYiPay = new RuYiPay();
        $ruYiPay->setPrivateKey('test');
        $ruYiPay->setOptions($sourceData);
        $ruYiPay->getVerifyData();
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
            'number' => '1002615',
            'orderId' => '201801020000003458',
            'amount' => '0.02',
            'notify_url' => 'http://pay.my/pay/return.php',
            'paymentVendorId' => '9999',
        ];

        $ruYiPay = new RuYiPay();
        $ruYiPay->setPrivateKey('test');
        $ruYiPay->setOptions($sourceData);
        $ruYiPay->getVerifyData();
    }

    /**
     * 測試支付成功(網銀)
     */
    public function testBankSuccess()
    {
        $sourceData = [
            'number' => '1002615',
            'orderId' => '201801020000003458',
            'amount' => '0.02',
            'notify_url' => 'http://pay.my/pay/return.php',
            'paymentVendorId' => '1',
        ];

        $ruYiPay = new RuYiPay();
        $ruYiPay->setPrivateKey('test');
        $ruYiPay->setOptions($sourceData);
        $requestData = $ruYiPay->getVerifyData();

        $this->assertEquals('1002615', $requestData['P_UserId']);
        $this->assertEquals('201801020000003458', $requestData['P_OrderId']);
        $this->assertEquals('', $requestData['P_CardID']);
        $this->assertEquals('', $requestData['P_CardPass']);
        $this->assertEquals('0.02', $requestData['P_FaceValue']);
        $this->assertEquals('1', $requestData['P_ChannelID']);
        $this->assertEquals('0.02', $requestData['P_Price']);
        $this->assertEquals('10001', $requestData['P_Description']);
        $this->assertEquals('http://pay.my/pay/return.php', $requestData['P_Result_URL']);
        $this->assertEquals('http://pay.my/pay/return.php', $requestData['P_Notify_URL']);
        $this->assertEquals('0aca94e4a8f549616230b08db978ee69', $requestData['P_PostKey']);
    }

    /**
     * 測試支付成功(二維)
     */
    public function testQrCodeSuccess()
    {
        $sourceData = [
            'number' => '1002615',
            'orderId' => '201801020000003458',
            'amount' => '0.02',
            'notify_url' => 'http://pay.my/pay/return.php',
            'paymentVendorId' => '1090',
        ];

        $ruYiPay = new RuYiPay();
        $ruYiPay->setPrivateKey('test');
        $ruYiPay->setOptions($sourceData);
        $requestData = $ruYiPay->getVerifyData();

        $this->assertEquals('1002615', $requestData['P_UserId']);
        $this->assertEquals('201801020000003458', $requestData['P_OrderId']);
        $this->assertEquals('', $requestData['P_CardID']);
        $this->assertEquals('', $requestData['P_CardPass']);
        $this->assertEquals('0.02', $requestData['P_FaceValue']);
        $this->assertEquals('21', $requestData['P_ChannelID']);
        $this->assertEquals('0.02', $requestData['P_Price']);
        $this->assertEquals('http://pay.my/pay/return.php', $requestData['P_Result_URL']);
        $this->assertEquals('http://pay.my/pay/return.php', $requestData['P_Notify_URL']);
        $this->assertEquals('2042c6745ec2b31f67919b0ac4b48249', $requestData['P_PostKey']);
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

        $ruYiPay = new RuYiPay();
        $ruYiPay->verifyOrderPayment([]);
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

        $ruYiPay = new RuYiPay();
        $ruYiPay->setPrivateKey('test');
        $ruYiPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時支付平台缺少回傳P_PostKey
     */
    public function testReturnWithoutPPostKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $sourceData = [
            'P_UserId' => '1002615',
            'P_OrderId' => '201801020000003458',
            'P_CardId' => '',
            'P_CardPass' => '',
            'P_FaceValue' => '0.02000',
            'P_ChannelId' => '1',
            'P_PayMoney' => '0.02',
            'P_Subject' => '',
            'P_Price' => '0.0200',
            'P_Quantity' => '0',
            'P_Description' => '10001',
            'P_Notic' => '',
            'P_ErrCode' => '0',
            'P_ErrMsg' => '支付成功',
        ];

        $ruYiPay = new RuYiPay();
        $ruYiPay->setPrivateKey('test');
        $ruYiPay->setOptions($sourceData);
        $ruYiPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時P_PostKey簽名驗證錯誤
     */
    public function testReturnPPostKeyVerificationFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Signature verification failed',
            180034
        );

        $sourceData = [
            'P_UserId' => '1002615',
            'P_OrderId' => '201801020000003458',
            'P_CardId' => '',
            'P_CardPass' => '',
            'P_FaceValue' => '0.02000',
            'P_ChannelId' => '1',
            'P_PayMoney' => '0.02',
            'P_Subject' => '',
            'P_Price' => '0.0200',
            'P_Quantity' => '0',
            'P_Description' => '10001',
            'P_Notic' => '',
            'P_ErrCode' => '0',
            'P_PostKey' => '9218083d040cc2ea8a7798254e6d7be1',
            'P_ErrMsg' => '支付成功',
        ];

        $ruYiPay = new RuYiPay();
        $ruYiPay->setPrivateKey('test');
        $ruYiPay->setOptions($sourceData);
        $ruYiPay->verifyOrderPayment([]);
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

        $sourceData = [
            'P_UserId' => '1002615',
            'P_OrderId' => '201801020000003458',
            'P_CardId' => '',
            'P_CardPass' => '',
            'P_FaceValue' => '0.02000',
            'P_ChannelId' => '1',
            'P_PayMoney' => '0.02',
            'P_Subject' => '',
            'P_Price' => '0.0200',
            'P_Quantity' => '0',
            'P_Description' => '10001',
            'P_Notic' => '',
            'P_ErrCode' => '1',
            'P_PostKey' => '3e68c8db09fce0d7fb738876054d7a8e',
            'P_ErrMsg' => '支付失败',
        ];

        $ruYiPay = new RuYiPay();
        $ruYiPay->setPrivateKey('test');
        $ruYiPay->setOptions($sourceData);
        $ruYiPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回結果為訂單編號錯誤
     */
    public function testReturnOrderIdError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $sourceData = [
            'P_UserId' => '1002615',
            'P_OrderId' => '201801020000003458',
            'P_CardId' => '',
            'P_CardPass' => '',
            'P_FaceValue' => '0.02000',
            'P_ChannelId' => '1',
            'P_PayMoney' => '0.02',
            'P_Subject' => '',
            'P_Price' => '0.0200',
            'P_Quantity' => '0',
            'P_Description' => '10001',
            'P_Notic' => '',
            'P_ErrCode' => '0',
            'P_PostKey' => '41206ab8920f308f415481bc5d3fccf4',
            'P_ErrMsg' => '支付成功',
        ];

        $entry = [
            'id' => '201801020000003459',
            'amount' => '0.02',
        ];

        $ruYiPay = new RuYiPay();
        $ruYiPay->setPrivateKey('test');
        $ruYiPay->setOptions($sourceData);
        $ruYiPay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果為訂單金額錯誤
     */
    public function testReturnAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

         $sourceData = [
            'P_UserId' => '1002615',
            'P_OrderId' => '201801020000003458',
            'P_CardId' => '',
            'P_CardPass' => '',
            'P_FaceValue' => '0.02000',
            'P_ChannelId' => '1',
            'P_PayMoney' => '0.02',
            'P_Subject' => '',
            'P_Price' => '0.0200',
            'P_Quantity' => '0',
            'P_Description' => '10001',
            'P_Notic' => '',
            'P_ErrCode' => '0',
            'P_PostKey' => '41206ab8920f308f415481bc5d3fccf4',
            'P_ErrMsg' => '支付成功',
        ];

        $entry = [
            'id' => '201801020000003458',
            'amount' => '2',
        ];

        $ruYiPay = new RuYiPay();
        $ruYiPay->setPrivateKey('test');
        $ruYiPay->setOptions($sourceData);
        $ruYiPay->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testReturnSuccess()
    {
        $sourceData = [
            'P_UserId' => '1002615',
            'P_OrderId' => '201801020000003458',
            'P_CardId' => '',
            'P_CardPass' => '',
            'P_FaceValue' => '0.02000',
            'P_ChannelId' => '1',
            'P_PayMoney' => '0.02',
            'P_Subject' => '',
            'P_Price' => '0.0200',
            'P_Quantity' => '0',
            'P_Description' => '10001',
            'P_Notic' => '',
            'P_ErrCode' => '0',
            'P_PostKey' => '41206ab8920f308f415481bc5d3fccf4',
            'P_ErrMsg' => '支付成功',
        ];

        $entry = [
            'id' => '201801020000003458',
            'amount' => '0.02',
        ];

        $ruYiPay = new RuYiPay();
        $ruYiPay->setPrivateKey('test');
        $ruYiPay->setOptions($sourceData);
        $ruYiPay->verifyOrderPayment($entry);

        $this->assertEquals('ErrCode=0', $ruYiPay->getMsg());
    }
}
