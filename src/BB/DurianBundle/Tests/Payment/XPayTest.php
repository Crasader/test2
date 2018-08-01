<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\XPay;

class XPayTest extends DurianTestCase
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

        $xpay = new XPay();
        $xpay->getVerifyData();
    }

    /**
     * 測試支付時沒未指定支付參數
     */
    public function testPayWithNoPayParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No pay parameter specified',
            180145
        );

        $xpay = new XPay();
        $xpay->setPrivateKey('test');
        $xpay->getVerifyData();
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
            'number' => '123456',
            'username' => 'test',
            'ip' => '127.0.0.1',
            'amount' => '100',
            'orderId' => '201606060000000001',
            'orderCreateDate' => '2016-06-13 15:40:00',
            'notify_url' => 'http://test.com/pay/',
            'paymentVendorId' => '100',
        ];

        $xpay = new XPay();
        $xpay->setPrivateKey('test');
        $xpay->setOptions($options);
        $xpay->getVerifyData();
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $options = [
            'number' => '123456',
            'username' => 'test',
            'ip' => '127.0.0.1',
            'amount' => '100',
            'orderId' => '201606060000000001',
            'orderCreateDate' => '2016-06-13 15:40:00',
            'notify_url' => 'http://test.com/pay/',
            'paymentVendorId' => '1',
        ];

        $xpay = new XPay();
        $xpay->setPrivateKey('test');
        $xpay->setOptions($options);
        $data = $xpay->getVerifyData();

        $encryptData = '4dg65h72G63k68g61J6eK74I49h44i3dj31H32g33h34G35k36g26J43K75I73h74i49j44H3dg74h65G73k74g26J43' .
            'K75I72h72i3dj43H4eg59h26G41k6dg6fJ75K6eI74h3di31j30H30g2eh30G30k26g52J65K66I49h44i3dj32H30g31h36G30k36g' .
            '30J36K30I30h30i30j30H30g30h30G30k31g26J54K72I61h6ei73j54H69g6dh65G3dk32g30J31K36I2dh30i36j2dH31g33h20G' .
            '31k35g3aJ34K30I3ah30i30j26H52g65h74G75k72g6eJ55K52I4ch3di68j74H74g70h3aG2fk2fg74J65K73I74h2ei63j6fH6dg2' .
            'fh70G61k79g2fJ26K52I65h71i75j65H73g74h55G52k4cg3dJ68K74I74h70i3aj2fH2fg74h65G73k74g2eJ63K6fI6dh2fi' .
            '70j61H79g2fh26G42k61g6eJ6bK43I6fh64i65j3dH49g43h42G43k';

        $this->assertEquals('', $data['Remarks']);
        $this->assertEquals('73efddc5f2a55c9d1a5d9f93f25f779f', $data['EncryptText']);
        $this->assertEquals($encryptData, $data['Data']);
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

        $xpay = new XPay();
        $xpay->verifyOrderPayment([]);
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

        $xpay = new XPay();
        $xpay->setPrivateKey('test');
        $xpay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時缺少簽名
     */
    public function testReturnWithoutEncryptText()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $options = [
            'RefID' => '201605310000003892',
            'Curr' => 'CNY',
            'Amount' => '1.00',
            'Status' => '002',
            'TransID' => '2016053102020251',
            'ValidationKey' => '15217384',
        ];

        $xpay = new XPay();
        $xpay->setPrivateKey('test');
        $xpay->setOptions($options);
        $xpay->verifyOrderPayment([]);
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
            'RefID' => '201605310000003892',
            'Curr' => 'CNY',
            'Amount' => '1.00',
            'Status' => '002',
            'TransID' => '2016053102020251',
            'ValidationKey' => '15217384',
            'EncryptText' => '',
        ];

        $xpay = new XPay();
        $xpay->setPrivateKey('test');
        $xpay->setOptions($options);
        $xpay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時訂單處理中
     */
    public function testReturnOrderProcessing()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Order Processing',
            180059
        );

        $options = [
            'RefID' => '201605310000003892',
            'Curr' => 'CNY',
            'Amount' => '1.00',
            'Status' => '001',
            'TransID' => '2016053102020251',
            'ValidationKey' => '15217384',
            'EncryptText' => 'DFD7055C2270079089CFF866E6E3F2E2',
        ];

        $xpay = new XPay();
        $xpay->setPrivateKey('test');
        $xpay->setOptions($options);
        $xpay->verifyOrderPayment([]);
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
            'RefID' => '201605310000003892',
            'Curr' => 'CNY',
            'Amount' => '1.00',
            'Status' => '007',
            'TransID' => '2016053102020251',
            'ValidationKey' => '15217384',
            'EncryptText' => 'F9332951180C4139EC5D943BD42E452F',
        ];

        $xpay = new XPay();
        $xpay->setPrivateKey('test');
        $xpay->setOptions($options);
        $xpay->verifyOrderPayment([]);
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
            'RefID' => '201605310000003892',
            'Curr' => 'CNY',
            'Amount' => '1.00',
            'Status' => '002',
            'TransID' => '2016053102020251',
            'ValidationKey' => '15217384',
            'EncryptText' => 'F758EF6C23F244CEF41E297A03A0DCC7',
        ];

        $entry = ['id' => '201605310000003893'];

        $xpay = new XPay();
        $xpay->setPrivateKey('test');
        $xpay->setOptions($options);
        $xpay->verifyOrderPayment($entry);
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
            'RefID' => '201605310000003892',
            'Curr' => 'CNY',
            'Amount' => '1.00',
            'Status' => '002',
            'TransID' => '2016053102020251',
            'ValidationKey' => '15217384',
            'EncryptText' => 'F758EF6C23F244CEF41E297A03A0DCC7',
        ];

        $entry = [
            'id' => '201605310000003892',
            'amount' => '15.00',
        ];

        $xpay = new XPay();
        $xpay->setPrivateKey('test');
        $xpay->setOptions($options);
        $xpay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果
     */
    public function testReturnOrder()
    {
        $options = [
            'RefID' => '201605310000003892',
            'Curr' => 'CNY',
            'Amount' => '1.00',
            'Status' => '002',
            'TransID' => '2016053102020251',
            'ValidationKey' => '15217384',
            'EncryptText' => 'F758EF6C23F244CEF41E297A03A0DCC7',
        ];

        $entry = [
            'id' => '201605310000003892',
            'amount' => '1.00',
        ];

        $xpay = new XPay();
        $xpay->setPrivateKey('test');
        $xpay->setOptions($options);
        $xpay->verifyOrderPayment($entry);

        $this->assertEquals('2016053102020251||15217384', $xpay->getMsg());
    }
}
