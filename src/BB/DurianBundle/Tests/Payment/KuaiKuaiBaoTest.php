<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\KuaiKuaiBao;

class KuaiKuaiBaoTest extends DurianTestCase
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

        $kuaiKuaiBao = new KuaiKuaiBao();
        $kuaiKuaiBao->getVerifyData();
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

        $kuaiKuaiBao = new KuaiKuaiBao();
        $kuaiKuaiBao->setPrivateKey('test');
        $kuaiKuaiBao->setOptions(['number' => '']);
        $kuaiKuaiBao->getVerifyData();
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
            'number' => '9527',
            'paymentVendorId' => '100',
            'orderCreateDate' => '2017-09-05 17:59:53',
            'orderId' => '20170917000009453',
            'amount' => '100',
            'notify_url' => 'http://www.yes9527.com.tw',
            'merchant_extra' => ['TerminalID' => '9527'],
        ];

        $kuaiKuaiBao = new KuaiKuaiBao();
        $kuaiKuaiBao->setPrivateKey('test');
        $kuaiKuaiBao->setOptions($sourceData);
        $kuaiKuaiBao->getVerifyData();
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
            'number' => '9527',
            'paymentVendorId' => '1',
            'orderCreateDate' => '2017-09-05 17:59:53',
            'orderId' => '20170917000009453',
            'amount' => '100',
            'notify_url' => 'http://www.yes9527.com.tw',
            'merchant_extra' => [],
        ];

        $kuaiKuaiBao = new KuaiKuaiBao();
        $kuaiKuaiBao->setPrivateKey('test');
        $kuaiKuaiBao->setOptions($sourceData);
        $kuaiKuaiBao->getVerifyData();
    }

    /**
     * 測試加密
     */
    public function testPay()
    {
        $sourceData = [
            'number' => '9527',
            'paymentVendorId' => '1',
            'orderCreateDate' => '2017-09-05 17:59:53',
            'orderId' => '20170917000009453',
            'amount' => '0.1',
            'notify_url' => 'http://www.yes9527.com.tw',
            'merchant_extra' => ['TerminalID' => '9527'],
        ];

        $kuaiKuaiBao = new KuaiKuaiBao();
        $kuaiKuaiBao->setPrivateKey('test');
        $kuaiKuaiBao->setOptions($sourceData);
        $requestData = $kuaiKuaiBao->getVerifyData();

        $this->assertSame($sourceData['number'], $requestData['MemberID']);
        $this->assertSame($sourceData['merchant_extra']['TerminalID'], $requestData['TerminalID']);
        $this->assertSame('4.0', $requestData['InterfaceVersion']);
        $this->assertSame('1', $requestData['KeyType']);
        $this->assertSame('3002', $requestData['PayID']);
        $this->assertSame('20170905175953', $requestData['TradeDate']);
        $this->assertSame($sourceData['orderId'], $requestData['TransID']);
        $this->assertSame($sourceData['amount'] * 100, $requestData['OrderMoney']);
        $this->assertSame('', $requestData['ProductName']);
        $this->assertSame('1', $requestData['Amount']);
        $this->assertSame('', $requestData['Username']);
        $this->assertSame('', $requestData['AdditionalInfo']);
        $this->assertSame('1', $requestData['NoticeType']);
        $this->assertSame($sourceData['notify_url'], $requestData['PageUrl']);
        $this->assertSame($sourceData['notify_url'], $requestData['ReturnUrl']);
        $this->assertSame('f5b14592c3a35802dd415a2af5d130e1', $requestData['Signature']);
    }

    /**
     * 測試解密驗證沒有帶入key的情況
     */
    public function testReturnWithoutKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $kuaiKuaiBao = new KuaiKuaiBao();
        $kuaiKuaiBao->verifyOrderPayment([]);
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
            'MemberID' => '9527',
            'TerminalID' => '9527',
            'TransID' => '20170905000009453',
            'ResultDesc' => '01',
            'FactMoney' => '100',
            'AdditionalInfo' => '',
            'SuccTime' => '20170905175913',
        ];

        $kuaiKuaiBao = new KuaiKuaiBao();
        $kuaiKuaiBao->setPrivateKey('test');
        $kuaiKuaiBao->setOptions($sourceData);
        $kuaiKuaiBao->verifyOrderPayment([]);
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
            'MemberID' => '9527',
            'TerminalID' => '9527',
            'TransID' => '20170905000009453',
            'Result' => '1',
            'ResultDesc' => '01',
            'FactMoney' => '100',
            'AdditionalInfo' => '',
            'SuccTime' => '20170905175913',
        ];

        $kuaiKuaiBao = new KuaiKuaiBao();
        $kuaiKuaiBao->setPrivateKey('test');
        $kuaiKuaiBao->setOptions($sourceData);
        $kuaiKuaiBao->verifyOrderPayment([]);
    }

    /**
     * 測試解密驗證返回時簽名驗證錯誤
     */
    public function testReturnSignatureVerificationFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Signature verification failed',
            180034
        );

        $sourceData = [
            'MemberID' => '9527',
            'TerminalID' => '9527',
            'TransID' => '20170905000009453',
            'Result' => '1',
            'ResultDesc' => '01',
            'FactMoney' => '100',
            'AdditionalInfo' => '',
            'SuccTime' => '20170905175913',
            'Md5Sign' => 'PdfOdfWedEdfIfdIdsSfdHfdAfNfdDfsSOME',
        ];

        $kuaiKuaiBao = new KuaiKuaiBao();
        $kuaiKuaiBao->setPrivateKey('test');
        $kuaiKuaiBao->setOptions($sourceData);
        $kuaiKuaiBao->verifyOrderPayment([]);
    }

    /**
     * 測試解密驗證返回時支付失敗
     */
    public function testReturnPaymentFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $sourceData = [
            'MemberID' => '9527',
            'TerminalID' => '9527',
            'TransID' => '20170905000009453',
            'Result' => '9',
            'ResultDesc' => '01',
            'FactMoney' => '100',
            'AdditionalInfo' => '',
            'SuccTime' => '20170905175913',
            'Md5Sign' => '511a3bfd213b73fc537b47acaa46892c',
        ];

        $kuaiKuaiBao = new KuaiKuaiBao();
        $kuaiKuaiBao->setPrivateKey('test');
        $kuaiKuaiBao->setOptions($sourceData);
        $kuaiKuaiBao->verifyOrderPayment([]);
    }

    /**
     * 測試解密驗證返回單號不正確
     */
    public function testReturnOrderIdError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $sourceData = [
            'MemberID' => '9527',
            'TerminalID' => '9527',
            'TransID' => '20170905000009453',
            'Result' => '1',
            'ResultDesc' => '01',
            'FactMoney' => '100',
            'AdditionalInfo' => '',
            'SuccTime' => '20170905175913',
            'Md5Sign' => 'f81d755feb2eb5531ddeec787f46d341',
        ];

        $entry = ['id' => '20140327000001456'];

        $kuaiKuaiBao = new KuaiKuaiBao();
        $kuaiKuaiBao->setPrivateKey('test');
        $kuaiKuaiBao->setOptions($sourceData);
        $kuaiKuaiBao->verifyOrderPayment($entry);
    }

    /**
     * 測試解密驗證返回金額不正確
     */
    public function testReturnOrderAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $sourceData = [
            'MemberID' => '9527',
            'TerminalID' => '9527',
            'TransID' => '20170905000009453',
            'Result' => '1',
            'ResultDesc' => '01',
            'FactMoney' => '100',
            'AdditionalInfo' => '',
            'SuccTime' => '20170905175913',
            'Md5Sign' => 'f81d755feb2eb5531ddeec787f46d341',
        ];

        $entry = [
            'id' => '20170905000009453',
            'amount' => '12340',
        ];

        $kuaiKuaiBao = new KuaiKuaiBao();
        $kuaiKuaiBao->setPrivateKey('test');
        $kuaiKuaiBao->setOptions($sourceData);
        $kuaiKuaiBao->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testReturnSuccess()
    {
        $sourceData = [
            'MemberID' => '9527',
            'TerminalID' => '9527',
            'TransID' => '20170905000009453',
            'Result' => '1',
            'ResultDesc' => '01',
            'FactMoney' => '100',
            'AdditionalInfo' => '',
            'SuccTime' => '20170905175913',
            'Md5Sign' => 'f81d755feb2eb5531ddeec787f46d341',
        ];

        $entry = [
            'id' => '20170905000009453',
            'amount' => '1',
        ];

        $kuaiKuaiBao = new KuaiKuaiBao();
        $kuaiKuaiBao->setPrivateKey('test');
        $kuaiKuaiBao->setOptions($sourceData);
        $kuaiKuaiBao->verifyOrderPayment($entry);

        $this->assertEquals('OK', $kuaiKuaiBao->getMsg());
    }
}
