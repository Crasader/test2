<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\LiZiPay;

class LiZiPayTest extends DurianTestCase
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

        $liZiPay = new LiZiPay();
        $liZiPay->getVerifyData();
    }

    /**
     * 測試支付時沒指定支付參數
     */
    public function testPayWithNoPayParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No pay parameter specified',
            180145
        );

        $liZiPay = new LiZiPay();
        $liZiPay->setPrivateKey('test');
        $liZiPay->getVerifyData();
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
            'notify_url' => 'http://orz.zz/',
            'paymentVendorId' => '9999',
            'number' => '6688008',
            'orderId' => '201805240000011487',
            'amount' => '1.01',
        ];

        $liZiPay = new LiZiPay();
        $liZiPay->setPrivateKey('test');
        $liZiPay->setOptions($options);
        $liZiPay->getVerifyData();
    }

    /**
     * 測試支付寶手機支付
     */
    public function testPayParameterSuccess()
    {
        $options = [
            'notify_url' => 'http://orz.zz/',
            'paymentVendorId' => '1098',
            'number' => '6688008',
            'orderId' => '201805240000011487',
            'amount' => '1.01',
        ];

        $liZiPay = new LiZiPay();
        $liZiPay->setPrivateKey('test');
        $liZiPay->setOptions($options);
        $requestData = $liZiPay->getVerifyData();

        $this->assertEquals('3.0', $requestData['version']);
        $this->assertEquals('LiZhi.online.interface', $requestData['method']);
        $this->assertEquals('6688008', $requestData['partner']);
        $this->assertEquals('ALIPAYWAP', $requestData['banktype']);
        $this->assertEquals('1.01', $requestData['paymoney']);
        $this->assertEquals('201805240000011487', $requestData['ordernumber']);
        $this->assertEquals('http://orz.zz/', $requestData['callbackurl']);
        $this->assertEquals('1', $requestData['isshow']);
        $this->assertEquals('b0d6f8c07e94bad465c5ab32cc7c6d1f', $requestData['sign']);
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

        $liZiPay = new LiZiPay();
        $liZiPay->verifyOrderPayment([]);
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

        $liZiPay = new LiZiPay();
        $liZiPay->setPrivateKey('test');
        $liZiPay->verifyOrderPayment([]);
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

        $options = [
            'partner' => '6688008',
            'ordernumber' => '201805240000011487',
            'orderstatus' => '1',
            'paymoney' => '1.01',
            'sysnumber' => '',
            'attach' => '',
        ];

        $liZiPay = new LiZiPay();
        $liZiPay->setPrivateKey('test');
        $liZiPay->setOptions($options);
        $liZiPay->verifyOrderPayment([]);
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
            'partner' => '6688008',
            'ordernumber' => '201805240000011487',
            'orderstatus' => '1',
            'paymoney' => '1.01',
            'sysnumber' => '',
            'attach' => '',
            'sign' => '123456789',
        ];

        $liZiPay = new LiZiPay();
        $liZiPay->setPrivateKey('test');
        $liZiPay->setOptions($options);
        $liZiPay->verifyOrderPayment([]);
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

        $options = [
            'partner' => '6688008',
            'ordernumber' => '201805240000011487',
            'orderstatus' => '2',
            'paymoney' => '1.01',
            'sysnumber' => '',
            'attach' => '',
            'sign' => '0c2ad4b1682b8c263b82ca88f2287bf9',
        ];

        $liZiPay = new LiZiPay();
        $liZiPay->setPrivateKey('test');
        $liZiPay->setOptions($options);
        $liZiPay->verifyOrderPayment([]);
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
            'partner' => '6688008',
            'ordernumber' => '201805240000011487',
            'orderstatus' => '1',
            'paymoney' => '1.01',
            'sysnumber' => '',
            'attach' => '',
            'sign' => '5e69b4fbb328edfe9e1e37cb7499cced',
        ];

        $entry = ['id' => '301805240000011487'];

        $liZiPay = new LiZiPay();
        $liZiPay->setPrivateKey('test');
        $liZiPay->setOptions($options);
        $liZiPay->verifyOrderPayment($entry);
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
            'partner' => '6688008',
            'ordernumber' => '201805240000011487',
            'orderstatus' => '1',
            'paymoney' => '1.01',
            'sysnumber' => '',
            'attach' => '',
            'sign' => '5e69b4fbb328edfe9e1e37cb7499cced',
        ];

        $entry = [
            'id' => '201805240000011487',
            'amount' => '15.00',
        ];

        $liZiPay = new LiZiPay();
        $liZiPay->setPrivateKey('test');
        $liZiPay->setOptions($options);
        $liZiPay->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testReturnOrder()
    {
        $options = [
            'partner' => '6688008',
            'ordernumber' => '201805240000011487',
            'orderstatus' => '1',
            'paymoney' => '1.01',
            'sysnumber' => '',
            'attach' => '',
            'sign' => '5e69b4fbb328edfe9e1e37cb7499cced',
        ];

        $entry = [
            'id' => '201805240000011487',
            'amount' => '1.01',
        ];

        $liZiPay = new LiZiPay();
        $liZiPay->setPrivateKey('test');
        $liZiPay->setOptions($options);
        $liZiPay->verifyOrderPayment($entry);

        $this->assertEquals('ok', $liZiPay->getMsg());
    }
}
