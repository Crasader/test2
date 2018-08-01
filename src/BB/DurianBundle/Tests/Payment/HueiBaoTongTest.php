<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\HueiBaoTong;

class HueiBaoTongTest extends DurianTestCase
{
    /**
     * 測試加密基本參數設定沒有帶入privateKey的情況
     */
    public function testPayWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $hueiBaoTong = new HueiBaoTong();
        $hueiBaoTong->getVerifyData();
    }

    /**
     * 測試加密基本參數設定未指定支付參數
     */
    public function testPayWithoutParameter()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No pay parameter specified',
            180145
        );

        $hueiBaoTong = new HueiBaoTong();
        $hueiBaoTong->setPrivateKey('abcdefg');
        $hueiBaoTong->setOptions(['number' => '']);
        $hueiBaoTong->getVerifyData();
    }

    /**
     * 測試支付設定帶入不支援的銀行
     */
    public function testPayWithNotSupportedBank()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'PaymentVendor is not supported by PaymentGateway',
            180066
        );


        $sourceData = [
            'number' => '100000178',
            'paymentVendorId' => '100',
            'orderCreateDate' => '2014-03-26 12:09:53',
            'orderId' => '20140326000000123',
            'amount' => '1234',
            'notify_url' => 'http://www.baofoo.com/demo/UserNotIFy.aspx',
            'username' => 'hello123',
            'merchant_extra' => ['TerminalID' => '10000001'],
            'merchantId' => '12345',
            'domain' => '6',
        ];

        $hueiBaoTong = new HueiBaoTong();
        $hueiBaoTong->setPrivateKey('abcdefg');
        $hueiBaoTong->setOptions($sourceData);
        $hueiBaoTong->getVerifyData();
    }

    /**
     * 測試加密找不到商家的附加設定值
     */
    public function testPayWithoutTerminalID()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No merchant extra value specified',
            180143
        );

        $sourceData = [
            'number' => '100000178',
            'paymentVendorId' => '1102',
            'orderCreateDate' => '2014-03-26 12:09:53',
            'orderId' => '20140326000000123',
            'amount' => '1234',
            'notify_url' => 'http://www.baofoo.com/demo/UserNotIFy.aspx',
            'username' => 'hello123',
            'merchant_extra' => [],
            'merchantId' => '12345',
            'domain' => '6',
        ];

        $hueiBaoTong = new HueiBaoTong();
        $hueiBaoTong->setPrivateKey('abcdefg');
        $hueiBaoTong->setOptions($sourceData);
        $hueiBaoTong->getVerifyData();
    }

    /**
     * 測試加密
     */
    public function testPay()
    {
        $sourceData = [
            'number' => '100000178',
            'paymentVendorId' => '1102',
            'orderCreateDate' => '2014-03-26 12:09:53',
            'orderId' => '20140326000000123',
            'amount' => '1234',
            'notify_url' => 'http://www.baofoo.com/demo/UserNotIFy.aspx',
            'username' => 'hello123',
            'merchant_extra' => ['TerminalID' => '10000001'],
            'merchantId' => '12345',
            'domain' => '6',
        ];

        $hueiBaoTong = new HueiBaoTong();
        $hueiBaoTong->setPrivateKey('abcdefg');
        $hueiBaoTong->setOptions($sourceData);
        $encodeData = $hueiBaoTong->getVerifyData();

        $this->assertSame($sourceData['number'], $encodeData['MemberID']);
        $this->assertSame($sourceData['merchant_extra']['TerminalID'], $encodeData['TerminalID']);
        $this->assertSame('4.0', $encodeData['InterfaceVersion']);
        $this->assertSame('1', $encodeData['KeyType']);
        $this->assertSame('', $encodeData['PayID']);
        $this->assertSame('N', $encodeData['Wap']);
        $this->assertSame('20140326120953', $encodeData['TradeDate']);
        $this->assertSame($sourceData['orderId'], $encodeData['TransID']);
        $this->assertSame($sourceData['amount'], $encodeData['OrderMoney']);
        $this->assertSame('', $encodeData['ProductName']);
        $this->assertSame('1', $encodeData['Amount']);
        $this->assertSame($sourceData['username'], $encodeData['Username']);
        $this->assertSame($sourceData['notify_url'], $encodeData['PageUrl']);
        $this->assertSame($sourceData['notify_url'], $encodeData['ReturnUrl']);
        $this->assertSame('0abcc40a04ef1e231993708aaaa3a462', $encodeData['Signature']);
        $this->assertSame('1', $encodeData['NoticeType']);
    }

    /**
     * 測試解密基本參數設定沒有帶入key的情況
     */
    public function testReturnWithoutKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $hueiBaoTong = new HueiBaoTong();
        $hueiBaoTong->verifyOrderPayment([]);
    }

    /**
     * 測試解密驗證未指定返回參數
     */
    public function testReturnWithoutParameter()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $sourceData = [
            'MemberID' => '100000178',
            'TerminalID' => '10000001',
            'TransID' => '20140326000000123',
            'ResultDesc' => '01',
            'FactMoney' => '123400',
            'AdditionalInfo' => '',
            'SuccTime' => '20140328100612',
            'Md5Sign' => '1ffa97f00c1ae1550bf5c03d595a9496',
            'BankID' => '',
        ];

        $hueiBaoTong = new HueiBaoTong();
        $hueiBaoTong->setPrivateKey('abcdefg');
        $hueiBaoTong->setOptions($sourceData);
        $hueiBaoTong->verifyOrderPayment([]);
    }

    /**
     * 測試解密驗證時支付平台回傳缺少簽名
     */
    public function testReturnWithoutSignature()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $sourceData = [
            'MemberID' => '100000178',
            'TerminalID' => '10000001',
            'TransID' => '20140326000000123',
            'Result' => '1',
            'ResultDesc' => '01',
            'FactMoney' => '123400',
            'AdditionalInfo' => '',
            'SuccTime' => '20140328100612',
            'BankID' => '',
        ];

        $hueiBaoTong = new HueiBaoTong();
        $hueiBaoTong->setPrivateKey('abcdefg');
        $hueiBaoTong->setOptions($sourceData);
        $hueiBaoTong->verifyOrderPayment([]);
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
            'MemberID' => '100000178',
            'TerminalID' => '10000001',
            'TransID' => '20140326000000123',
            'Result' => '1',
            'ResultDesc' => '01',
            'FactMoney' => '123400',
            'AdditionalInfo' => '',
            'SuccTime' => '20140328100612',
            'Md5Sign' => 'c31a6b799ff4fda80fb2b94e8186ccce',
            'BankID' => '',
        ];

        $hueiBaoTong = new HueiBaoTong();
        $hueiBaoTong->setPrivateKey('abcdefg');
        $hueiBaoTong->setOptions($sourceData);
        $hueiBaoTong->verifyOrderPayment([]);
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
            'MemberID' => '100000178',
            'TerminalID' => '10000001',
            'TransID' => '20140326000000123',
            'Result' => '0',
            'ResultDesc' => '01',
            'FactMoney' => '123400',
            'AdditionalInfo' => '',
            'SuccTime' => '20140328100612',
            'Md5Sign' => '81de14bb545d46a9ea667ead3a99b3fe',
            'BankID' => '',
        ];

        $hueiBaoTong = new HueiBaoTong();
        $hueiBaoTong->setPrivateKey('abcdefg');
        $hueiBaoTong->setOptions($sourceData);
        $hueiBaoTong->verifyOrderPayment([]);
    }

    /**
     * 測試返回時支付失敗(回傳ResultDesc非01)
     */
    public function testReturnPaymentFailureWithResultDescError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $sourceData = [
            'MemberID' => '100000178',
            'TerminalID' => '10000001',
            'TransID' => '20140326000000123',
            'Result' => '1',
            'ResultDesc' => '0000',
            'FactMoney' => '123400',
            'AdditionalInfo' => '',
            'SuccTime' => '20140328100612',
            'Md5Sign' => '2191da0056fb4f843dbdd37876ff80e4',
            'BankID' => '',
        ];

        $hueiBaoTong = new HueiBaoTong();
        $hueiBaoTong->setPrivateKey('abcdefg');
        $hueiBaoTong->setOptions($sourceData);
        $hueiBaoTong->verifyOrderPayment([]);
    }

    /**
     * 測試返回單號不正確
     */
    public function testReturnOrderIdError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $sourceData = [
            'MemberID' => '100000178',
            'TerminalID' => '10000001',
            'TransID' => '20140326000000123',
            'Result' => '1',
            'ResultDesc' => '01',
            'FactMoney' => '123400',
            'AdditionalInfo' => '',
            'SuccTime' => '20140328100612',
            'Md5Sign' => '1ffa97f00c1ae1550bf5c03d595a9496',
            'BankID' => '',
        ];

        $entry = ['id' => '20140327000001456'];

        $hueiBaoTong = new HueiBaoTong();
        $hueiBaoTong->setPrivateKey('abcdefg');
        $hueiBaoTong->setOptions($sourceData);
        $hueiBaoTong->verifyOrderPayment($entry);
    }

    /**
     * 測試返回金額不正確
     */
    public function testReturnOrderAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $sourceData = [
            'MemberID' => '100000178',
            'TerminalID' => '10000001',
            'TransID' => '20140326000000123',
            'Result' => '1',
            'ResultDesc' => '01',
            'FactMoney' => '123400',
            'AdditionalInfo' => '',
            'SuccTime' => '20140328100612',
            'Md5Sign' => '1ffa97f00c1ae1550bf5c03d595a9496',
            'BankID' => '',
        ];

        $entry = [
            'id' => '20140326000000123',
            'amount' => '12340',
        ];

        $hueiBaoTong = new HueiBaoTong();
        $hueiBaoTong->setPrivateKey('abcdefg');
        $hueiBaoTong->setOptions($sourceData);
        $hueiBaoTong->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testReturnSuccess()
    {
        $sourceData = [
            'MemberID' => '100000178',
            'TerminalID' => '10000001',
            'TransID' => '20140326000000123',
            'Result' => '1',
            'ResultDesc' => '01',
            'FactMoney' => '1234',
            'AdditionalInfo' => '',
            'SuccTime' => '20140328100612',
            'Md5Sign' => '0c8db0e37aa8844683ed4b82d1f525fd',
            'BankID' => '',
        ];

        $entry = [
            'id' => '20140326000000123',
            'amount' => '1234',
        ];

        $hueiBaoTong = new HueiBaoTong();
        $hueiBaoTong->setPrivateKey('abcdefg');
        $hueiBaoTong->setOptions($sourceData);
        $hueiBaoTong->verifyOrderPayment($entry);

        $this->assertEquals('ok', $hueiBaoTong->getMsg());
    }
}
