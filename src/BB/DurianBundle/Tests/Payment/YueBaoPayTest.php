<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Payment\YueBaoPay;
use BB\DurianBundle\Tests\DurianTestCase;

class YueBaoPayTest extends DurianTestCase
{
    /**
     * 測試支付時缺少私鑰
     */
    public function testPayWithPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $yueBaoPay = new YueBaoPay();
        $yueBaoPay->getVerifyData();
    }

    /**
     * 測試支付時沒有指定支付參數
     */
    public function testPayWithNoPayParameterSpecified()
    {
        $this->setExpectedException(
            'BB\Durianbundle\Exception\PaymentException',
            'No pay parameter specified',
            180145
        );

        $yueBaoPay = new YueBaoPay();
        $yueBaoPay->setPrivateKey('test');
        $yueBaoPay->getVerifyData();
    }

    /**
     * 測試支付時帶入不支援銀行
     */
    public function testPaySourceNotSupportBank()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'PaymentVendor is not supported by PaymentGateway',
            180066
        );

        $sourceData = [
            'notify_url' => 'http://pay.my/pay/return.php',
            'paymentVendorId' => '9999',
            'number' => '880518',
            'orderId' => '201710030000001385',
            'amount' => '1.00',
        ];

        $yueBaoPay = new YueBaoPay();
        $yueBaoPay->setPrivateKey('test');
        $yueBaoPay->setOptions($sourceData);
        $yueBaoPay->getVerifyData();
    }

    /**
     * 測試支付成功
     */
    public function testPaySuccess()
    {
        $sourceData = [
            'notify_url' => 'http://pay.my/pay/return.php',
            'paymentVendorId' => '1090',
            'number' => '880518',
            'orderId' => '201710030000001385',
            'amount' => '1.00',
        ];

        $yueBaoPay = new YueBaoPay();
        $yueBaoPay->setPrivateKey('test');
        $yueBaoPay->setOptions($sourceData);
        $requestData = $yueBaoPay->getVerifyData();

        $this->assertEquals('3.0', $requestData['version']);
        $this->assertEquals('Yb.online.interface', $requestData['method']);
        $this->assertEquals('880518', $requestData['partner']);
        $this->assertEquals('WEIXIN', $requestData['banktype']);
        $this->assertEquals('1', $requestData['paymoney']);
        $this->assertEquals('201710030000001385', $requestData['ordernumber']);
        $this->assertEquals('http://pay.my/pay/return.php', $requestData['callbackurl']);
        $this->assertEquals('', $requestData['hrefbackurl']);
        $this->assertEquals('', $requestData['attach']);
        $this->assertEquals('1', $requestData['isshow']);
        $this->assertEquals('4530e49fdb4084a4f137a8839b612795', $requestData['sign']);
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

        $yueBaoPay = new YueBaoPay();
        $yueBaoPay->verifyOrderPayment([]);
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

        $yueBaoPay = new YueBaoPay();
        $yueBaoPay->setPrivateKey('test');
        $yueBaoPay->verifyOrderPayment([]);
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
            'partner' => '880518',
            'ordernumber' => '201710030000001385',
            'orderstatus' => '1',
            'paymoney' => '1.0000',
            'sysnumber' => 'yb880518171003105721245',
            'attach' => '',
        ];

        $yueBaoPay = new YueBaoPay();
        $yueBaoPay->setPrivateKey('test');
        $yueBaoPay->setOptions($sourceData);
        $yueBaoPay->verifyOrderPayment([]);
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
            'partner' => '880518',
            'ordernumber' => '201710030000001385',
            'orderstatus' => '1',
            'paymoney' => '1.0000',
            'sysnumber' => 'yb880518171003105721245',
            'attach' => '',
            'sign' => '7385985c7508de44ad046a9b4a1c2100',
        ];

        $yueBaoPay = new YueBaoPay();
        $yueBaoPay->setPrivateKey('test');
        $yueBaoPay->setOptions($sourceData);
        $yueBaoPay->verifyOrderPayment([]);
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
            'partner' => '880518',
            'ordernumber' => '201710030000001385',
            'orderstatus' => '0',
            'paymoney' => '1.0000',
            'sysnumber' => 'yb880518171003105721245',
            'attach' => '',
            'sign' => '2720cf9335500fb8094ac7a76b3b7ebf',
        ];

        $yueBaoPay = new YueBaoPay();
        $yueBaoPay->setPrivateKey('test');
        $yueBaoPay->setOptions($sourceData);
        $yueBaoPay->verifyOrderPayment([]);
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
            'partner' => '880518',
            'ordernumber' => '201710030000001385',
            'orderstatus' => '1',
            'paymoney' => '1.0000',
            'sysnumber' => 'yb880518171003105721245',
            'attach' => '',
            'sign' => 'd203875320ef3b1844c26c9bf4cd0354',
        ];

        $entry = [
            'id' => '201710030000001386',
            'amount' => '1.0000',
        ];

        $yueBaoPay = new YueBaoPay();
        $yueBaoPay->setPrivateKey('test');
        $yueBaoPay->setOptions($sourceData);
        $yueBaoPay->verifyOrderPayment($entry);
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
            'partner' => '880518',
            'ordernumber' => '201710030000001385',
            'orderstatus' => '1',
            'paymoney' => '1.0000',
            'sysnumber' => 'yb880518171003105721245',
            'attach' => '',
            'sign' => 'd203875320ef3b1844c26c9bf4cd0354',
        ];

        $entry = [
            'id' => '201710030000001385',
            'amount' => '2.0000',
        ];

        $yueBaoPay = new YueBaoPay();
        $yueBaoPay->setPrivateKey('test');
        $yueBaoPay->setOptions($sourceData);
        $yueBaoPay->verifyOrderPayment($entry);
    }

    /**
     * 測試支付認證成功
     */
    public function testReturnOrder()
    {
        $sourceData = [
            'partner' => '880518',
            'ordernumber' => '201710030000001385',
            'orderstatus' => '1',
            'paymoney' => '1.0000',
            'sysnumber' => 'yb880518171003105721245',
            'attach' => '',
            'sign' => 'd203875320ef3b1844c26c9bf4cd0354',
        ];

        $entry = [
            'id' => '201710030000001385',
            'amount' => '1.0000',
        ];

        $yueBaoPay = new YueBaoPay();
        $yueBaoPay->setPrivateKey('test');
        $yueBaoPay->setOptions($sourceData);
        $yueBaoPay->verifyOrderPayment($entry);

        $this->assertEquals('ok', $yueBaoPay->getMsg());
    }
}