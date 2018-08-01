<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\ShanFu;

class ShanFuTest extends DurianTestCase
{
    /**
     * 測試加密基本參數設定沒有帶入privateKey的情況
     */
    public function testSetEncodeSourceNoPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $shanFu = new ShanFu();
        $shanFu->getVerifyData();
    }

    /**
     * 測試加密基本參數設定未指定支付參數
     */
    public function testSetEncodeSourceNoReturnParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No pay parameter specified',
            180145
        );

        $shanFu = new ShanFu();
        $shanFu->setPrivateKey('abcdefg');

        $sourceData = ['number' => ''];

        $shanFu->setOptions($sourceData);
        $shanFu->getVerifyData();
    }

    /**
     * 測試加密基本參數設定帶入不支援的銀行
     */
    public function testSetEncodeSourceNotSupportBank()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'PaymentVendor is not supported by PaymentGateway',
            180066
        );

        $shanFu = new ShanFu();
        $shanFu->setPrivateKey('abcdefg');

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

        $shanFu->setOptions($sourceData);
        $shanFu->getVerifyData();
    }

    /**
     * 測試加密
     */
    public function testGetEncodeData()
    {
        $shanFu = new ShanFu();
        $shanFu->setPrivateKey('abcdefg');

        $sourceData = [
            'number' => '100000178',
            'paymentVendorId' => '1',
            'orderCreateDate' => '2014-03-26 12:09:53',
            'orderId' => '20140326000000123',
            'amount' => '1234',
            'notify_url' => 'http://www.baofoo.com/demo/UserNotIFy.aspx',
            'username' => 'hello123',
            'merchant_extra' => ['TerminalID' => '10000001'],
            'merchantId' => '12345',
            'domain' => '6',
        ];

        $shanFu->setOptions($sourceData);
        $encodeData = $shanFu->getVerifyData();

        $notifyUrl = sprintf(
            '%s?pay_system=%s&hallid=%s',
            $sourceData['notify_url'],
            $sourceData['merchantId'],
            $sourceData['domain']
        );

        $this->assertEquals($sourceData['number'], $encodeData['MemberID']);
        $this->assertEquals('3002', $encodeData['PayID']);
        $this->assertEquals('20140326120953', $encodeData['TradeDate']);
        $this->assertEquals($sourceData['orderId'], $encodeData['TransID']);
        $this->assertEquals($sourceData['amount'] * 100, $encodeData['OrderMoney']);
        $this->assertEquals('hello123', $encodeData['Username']);
        $this->assertEquals($notifyUrl, $encodeData['PageUrl']);
        $this->assertEquals($notifyUrl, $encodeData['ReturnUrl']);
        $this->assertEquals('8dfca585da5963f4a457e471a682862b', $encodeData['Md5Sign']);
    }

    /**
     * 測試加密,找不到商家的TerminalID附加設定值
     */
    public function testGetEncodeDataButNoTerminalIDSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No merchant extra value specified',
            180143
        );

        $shanFu = new ShanFu();
        $shanFu->setPrivateKey('abcdefg');

        $sourceData = [
            'number' => '100000178',
            'paymentVendorId' => '1',
            'orderCreateDate' => '2014-03-26 12:09:53',
            'orderId' => '20140326000000123',
            'amount' => '1234',
            'notify_url' => 'http://www.baofoo.com/demo/UserNotIFy.aspx',
            'username' => 'hello123',
            'merchant_extra' => [],
            'merchantId' => '12345',
            'domain' => '6',
        ];

        $shanFu->setOptions($sourceData);
        $shanFu->getVerifyData();
    }

    /**
     * 測試解密基本參數設定沒有帶入key的情況
     */
    public function testSetDecodeSourceNoKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $shanFu = new ShanFu();

        $shanFu->verifyOrderPayment([]);
    }

    /**
     * 測試解密驗證時支付平台未指定返回參數
     */
    public function testVerifyWithoutResult()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $shanFu = new ShanFu();
        $shanFu->setPrivateKey('abcdefg');

        $sourceData = [
            'MemberID' => '100000178',
            'TerminalID' => '10000001',
            'TransID' => '20140326000000123',
            'ResultDesc' => '01',
            'FactMoney' => '123400',
            'AdditionalInfo' => '',
            'SuccTime' => '20140328100612',
            'Md5Sign' => '1ffa97f00c1ae1550bf5c03d595a9496'
        ];

        $shanFu->setOptions($sourceData);
        $shanFu->verifyOrderPayment([]);
    }

    /**
     * 測試解密驗證時沒有支付平台缺少回傳參數(測試Md5Sign:加密簽名)
     */
    public function testVerifyWithoutMd5Sign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $shanFu = new ShanFu();
        $shanFu->setPrivateKey('abcdefg');

        $sourceData = [
            'MemberID' => '100000178',
            'TerminalID' => '10000001',
            'TransID' => '20140326000000123',
            'Result' => '1',
            'ResultDesc' => '01',
            'FactMoney' => '123400',
            'AdditionalInfo' => '',
            'SuccTime' => '20140328100612'
        ];

        $shanFu->setOptions($sourceData);
        $shanFu->verifyOrderPayment([]);
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

        $shanFu = new ShanFu();
        $shanFu->setPrivateKey('abcdefg');

        $sourceData = [
            'MemberID' => '100000178',
            'TerminalID' => '10000001',
            'TransID' => '20140326000000123',
            'Result' => '1',
            'ResultDesc' => '01',
            'FactMoney' => '123400',
            'AdditionalInfo' => '',
            'SuccTime' => '20140328100612',
            'Md5Sign' => 'c31a6b799ff4fda80fb2b94e8186ccce'
        ];

        $shanFu->setOptions($sourceData);
        $shanFu->verifyOrderPayment([]);
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

        $shanFu = new ShanFu();
        $shanFu->setPrivateKey('abcdefg');

        $sourceData = [
            'MemberID' => '100000178',
            'TerminalID' => '10000001',
            'TransID' => '20140326000000123',
            'Result' => '0',
            'ResultDesc' => '01',
            'FactMoney' => '123400',
            'AdditionalInfo' => '',
            'SuccTime' => '20140328100612',
            'Md5Sign' => '81de14bb545d46a9ea667ead3a99b3fe'
        ];

        $shanFu->setOptions($sourceData);
        $shanFu->verifyOrderPayment([]);
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

        $shanFu = new ShanFu();
        $shanFu->setPrivateKey('abcdefg');

        $sourceData = [
            'MemberID' => '100000178',
            'TerminalID' => '10000001',
            'TransID' => '20140326000000123',
            'Result' => '1',
            'ResultDesc' => '0000',
            'FactMoney' => '123400',
            'AdditionalInfo' => '',
            'SuccTime' => '20140328100612',
            'Md5Sign' => '2191da0056fb4f843dbdd37876ff80e4'
        ];

        $shanFu->setOptions($sourceData);
        $shanFu->verifyOrderPayment([]);
    }

    /**
     * 測試線上支付單號不正確的情況
     */
    public function testOrderIdError()
    {
        $this->setExpectedException('BB\DurianBundle\Exception\PaymentException', 'Order Id error', 180061);

        $shanFu = new ShanFu();
        $shanFu->setPrivateKey('abcdefg');

        $sourceData = [
            'MemberID' => '100000178',
            'TerminalID' => '10000001',
            'TransID' => '20140326000000123',
            'Result' => '1',
            'ResultDesc' => '01',
            'FactMoney' => '123400',
            'AdditionalInfo' => '',
            'SuccTime' => '20140328100612',
            'Md5Sign' => '1ffa97f00c1ae1550bf5c03d595a9496'
        ];

        $entry = ['id' => '20140327000001456'];

        $shanFu->setOptions($sourceData);
        $shanFu->verifyOrderPayment($entry);
    }

    /**
     * 測試金額比對錯誤的情況
     */
    public function testAmountFailure()
    {
        $this->setExpectedException('BB\DurianBundle\Exception\PaymentException', 'Order Amount error', 180058);

        $shanFu = new ShanFu();
        $shanFu->setPrivateKey('abcdefg');

        $sourceData = [
            'MemberID' => '100000178',
            'TerminalID' => '10000001',
            'TransID' => '20140326000000123',
            'Result' => '1',
            'ResultDesc' => '01',
            'FactMoney' => '123400',
            'AdditionalInfo' => '',
            'SuccTime' => '20140328100612',
            'Md5Sign' => '1ffa97f00c1ae1550bf5c03d595a9496'
        ];

        $entry = [
            'id' => '20140326000000123',
            'amount' => '12340'
        ];

        $shanFu->setOptions($sourceData);
        $shanFu->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testPaySuccess()
    {
        $shanFu = new ShanFu();
        $shanFu->setPrivateKey('abcdefg');

        $sourceData = [
            'MemberID' => '100000178',
            'TerminalID' => '10000001',
            'TransID' => '20140326000000123',
            'Result' => '1',
            'ResultDesc' => '01',
            'FactMoney' => '123400',
            'AdditionalInfo' => '',
            'SuccTime' => '20140328100612',
            'Md5Sign' => '1ffa97f00c1ae1550bf5c03d595a9496'
        ];

        $entry = [
            'id' => '20140326000000123',
            'amount' => '1234'
        ];

        $shanFu->setOptions($sourceData);
        $shanFu->verifyOrderPayment($entry);

        $this->assertEquals('OK', $shanFu->getMsg());
    }
}
