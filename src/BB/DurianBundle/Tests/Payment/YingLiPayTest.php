<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\YingLiPay;

class YingLiPayTest extends DurianTestCase
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

        $yingLiPay = new YingLiPay();
        $yingLiPay->getVerifyData();
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

        $yingLiPay = new YingLiPay();
        $yingLiPay->setPrivateKey('test');
        $yingLiPay->setOptions($sourceData);
        $yingLiPay->getVerifyData();
    }

    /**
     * 測試未支援的銀行
     */
    public function testNoSupportVendor()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'PaymentVendor is not supported by PaymentGateway',
            180066
        );

        $sourceData = [
            'number' => '600001',
            'orderId' => '201804240000012451',
            'amount' => '1.00',
            'paymentVendorId' => '10000',
            'notify_url' => 'http://fufutest.000webhostapp.com/pay',
            'orderCreateDate' => '2018-04-24 06:08:30',
        ];

        $yingLiPay = new YingLiPay();
        $yingLiPay->setPrivateKey('test');
        $yingLiPay->setOptions($sourceData);
        $yingLiPay->getVerifyData();
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $sourceData = [
            'number' => '600001',
            'orderId' => '201804240000012451',
            'amount' => '1.00',
            'paymentVendorId' => '1103',
            'notify_url' => 'http://fufutest.000webhostapp.com/pay',
            'orderCreateDate' => '2018-04-24 06:08:30',
        ];

        $yingLiPay = new YingLiPay();
        $yingLiPay->setPrivateKey('test');
        $yingLiPay->setOptions($sourceData);
        $verifyData = $yingLiPay->getVerifyData();

        $this->assertEquals('600001', $verifyData['pay_memberid']);
        $this->assertEquals('201804240000012451', $verifyData['pay_orderid']);
        $this->assertEquals('1.00', $verifyData['pay_amount']);
        $this->assertEquals('2018-04-24 06:08:30', $verifyData['pay_applydate']);
        $this->assertEquals('0', $verifyData['pay_bankcode']);
        $this->assertEquals('http://fufutest.000webhostapp.com/pay', $verifyData['pay_notifyurl']);
        $this->assertEquals('http://fufutest.000webhostapp.com/pay', $verifyData['pay_callbackurl']);
        $this->assertEquals('0', $verifyData['tongdao']);
        $this->assertEquals('4', $verifyData['cashier']);
        $this->assertEquals('47509F1D338727E52DE4622630C9BE1D', $verifyData['pay_md5sign']);
    }

    /**
     * 測試網銀支付
     */
    public function testBankPay()
    {
        $sourceData = [
            'number' => '600001',
            'orderId' => '201804240000012451',
            'amount' => '1.00',
            'paymentVendorId' => '1',
            'notify_url' => 'http://fufutest.000webhostapp.com/pay',
            'orderCreateDate' => '2018-04-24 06:08:30',
        ];

        $yingLiPay = new YingLiPay();
        $yingLiPay->setPrivateKey('test');
        $yingLiPay->setOptions($sourceData);
        $verifyData = $yingLiPay->getVerifyData();

        $this->assertEquals('600001', $verifyData['pay_memberid']);
        $this->assertEquals('201804240000012451', $verifyData['pay_orderid']);
        $this->assertEquals('1.00', $verifyData['pay_amount']);
        $this->assertEquals('2018-04-24 06:08:30', $verifyData['pay_applydate']);
        $this->assertEquals('ICBC', $verifyData['pay_bankcode']);
        $this->assertEquals('http://fufutest.000webhostapp.com/pay', $verifyData['pay_notifyurl']);
        $this->assertEquals('http://fufutest.000webhostapp.com/pay', $verifyData['pay_callbackurl']);
        $this->assertEquals('ZL', $verifyData['tongdao']);
        $this->assertEquals('5', $verifyData['cashier']);
        $this->assertEquals('885FCF8F687758DD390636A025FB2523', $verifyData['pay_md5sign']);
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

        $yingLiPay = new YingLiPay();
        $yingLiPay->verifyOrderPayment([]);
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

        $yingLiPay = new YingLiPay();
        $yingLiPay->setPrivateKey('test');
        $yingLiPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時支付平台缺少回傳sign
     */
    public function testReturnWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $sourceData = [
            'amount' => '1.00',
            'datetime' => '2018-04-25',
            'orderid' => '201804240000012455',
            'returncode' => '00',
            'reserved3' => '',
            'reserved2' => '',
            'reserved1' => '',
            'memberid' => '600001',
        ];

        $yingLiPay = new YingLiPay();
        $yingLiPay->setPrivateKey('test');
        $yingLiPay->setOptions($sourceData);
        $yingLiPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時sign簽名驗證錯誤
     */
    public function testReturnSignatureVerificationFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Signature verification failed',
            180034
        );

        $sourceData = [
            'amount' => '1.00',
            'datetime' => '2018-04-25',
            'orderid' => '201804240000012455',
            'returncode' => '00',
            'reserved3' => '',
            'sign' => '256CED1D1F78D906F48B137729EBB39A',
            'reserved2' => '',
            'reserved1' => '',
            'memberid' => '600001',
        ];

        $yingLiPay = new YingLiPay();
        $yingLiPay->setPrivateKey('test');
        $yingLiPay->setOptions($sourceData);
        $yingLiPay->verifyOrderPayment([]);
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
            'amount' => '1.00',
            'datetime' => '2018-04-25',
            'orderid' => '201804240000012455',
            'returncode' => '1',
            'reserved3' => '',
            'sign' => 'E5985B2A92256B05D171A418A97B35AC',
            'reserved2' => '',
            'reserved1' => '',
            'memberid' => '600001',
        ];

        $yingLiPay = new YingLiPay();
        $yingLiPay->setPrivateKey('test');
        $yingLiPay->setOptions($sourceData);
        $yingLiPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回結果為訂單編號錯誤
     */
    public function testReturnOrderIdFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $sourceData = [
            'amount' => '1.00',
            'datetime' => '2018-04-25',
            'orderid' => '201804240000012455',
            'returncode' => '00',
            'reserved3' => '',
            'sign' => '022FE60727CEE409F79041F5476A1EBF',
            'reserved2' => '',
            'reserved1' => '',
            'memberid' => '600001',
        ];

        $entry = [
            'id' => '201709220000007788',
            'amount' => '50.90',
        ];

        $yingLiPay = new YingLiPay();
        $yingLiPay->setPrivateKey('test');
        $yingLiPay->setOptions($sourceData);
        $yingLiPay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果為訂單金額錯誤
     */
    public function testReturnAmountFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $sourceData = [
            'amount' => '1.00',
            'datetime' => '2018-04-25',
            'orderid' => '201804240000012455',
            'returncode' => '00',
            'reserved3' => '',
            'sign' => '022FE60727CEE409F79041F5476A1EBF',
            'reserved2' => '',
            'reserved1' => '',
            'memberid' => '600001',
        ];

        $entry = [
            'id' => '201804240000012455',
            'amount' => '59.00',
        ];

        $yingLiPay = new YingLiPay();
        $yingLiPay->setPrivateKey('test');
        $yingLiPay->setOptions($sourceData);
        $yingLiPay->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testPaySuccess()
    {
        $sourceData = [
            'amount' => '1.00',
            'datetime' => '2018-04-25',
            'orderid' => '201804240000012455',
            'returncode' => '00',
            'reserved3' => '',
            'sign' => '022FE60727CEE409F79041F5476A1EBF',
            'reserved2' => '',
            'reserved1' => '',
            'memberid' => '600001',
        ];

        $entry = [
            'id' => '201804240000012455',
            'amount' => '1.00',
        ];

        $yingLiPay = new YingLiPay();
        $yingLiPay->setPrivateKey('test');
        $yingLiPay->setOptions($sourceData);
        $yingLiPay->verifyOrderPayment($entry);

        $this->assertEquals('OK', $yingLiPay->getMsg());
    }
}
