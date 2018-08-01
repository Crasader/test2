<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\BaoFooWap;

class BaoFooWapTest extends DurianTestCase
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

        $baoFooWap = new BaoFooWap();
        $baoFooWap->getVerifyData();
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

        $options = ['number' => ''];

        $baoFooWap = new BaoFooWap();
        $baoFooWap->setPrivateKey('abcdefg');
        $baoFooWap->setOptions($options);
        $baoFooWap->getVerifyData();
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
            'number' => '100000178',
            'paymentVendorId' => '1',
            'orderCreateDate' => '2014-03-26 12:09:53',
            'orderId' => '20140326000000123',
            'amount' => '1234',
            'notify_url' => 'http://www.baofoo.com/demo/UserNotIFy.aspx',
            'username' => 'hello123',
            'merchant_extra' => ['terminalId' => '10000001'],
            'merchantId' => '12345',
            'domain' => '6',
        ];

        $baoFooWap = new BaoFooWap();
        $baoFooWap->setPrivateKey('abcdefg');
        $baoFooWap->setOptions($options);
        $baoFooWap->getVerifyData();
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $options = [
            'number' => '100000178',
            'paymentVendorId' => '1007',
            'orderCreateDate' => '2014-03-26 12:09:53',
            'orderId' => '20140326000000123',
            'amount' => '1234',
            'notify_url' => 'http://esball.org/app/member/pay_online2/pay_result.php',
            'username' => 'hello123',
            'merchant_extra' => ['terminalId' => '10000001'],
            'merchantId' => '12345',
            'domain' => '6',
        ];

        $baoFooWap = new BaoFooWap();
        $baoFooWap->setPrivateKey('abcdefg');
        $baoFooWap->setOptions($options);
        $requestData = $baoFooWap->getVerifyData();

        $notifyUrl = sprintf(
            '%s?pay_system=%s&hallid=%s',
            $options['notify_url'],
            $options['merchantId'],
            $options['domain']
        );

        $this->assertEquals($options['number'], $requestData['MemberID']);
        $this->assertEquals('20140326120953', $requestData['TradeDate']);
        $this->assertEquals($options['orderId'], $requestData['TransID']);
        $this->assertEquals($options['amount'] * 100, $requestData['OrderMoney']);
        $this->assertEquals($notifyUrl, $requestData['PageUrl']);
        $this->assertEquals($notifyUrl, $requestData['ReturnUrl']);
        $this->assertEquals('4020001', $requestData['PayID']);
        $this->assertEquals('hello123', $requestData['UserName']);
        $this->assertEquals('ec7c127a5274004245d1bf937c61b6bb', $requestData['Signature']);
    }

    /**
     * 測試支付時缺少商家額外的參數設定terminalId
     */
    public function testPayWithoutMerchantExtraTerminalId()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No merchant extra value specified',
            180143
        );

        $options = [
            'number' => '100000178',
            'paymentVendorId' => '1007',
            'orderCreateDate' => '2014-03-26 12:09:53',
            'orderId' => '20140326000000123',
            'amount' => '1234',
            'notify_url' => 'http://www.baofoo.com/demo/UserNotIFy.aspx',
            'username' => 'hello123',
            'merchant_extra' => [],
            'merchantId' => '12345',
            'domain' => '6',
        ];

        $baoFooWap = new BaoFooWap();
        $baoFooWap->setPrivateKey('abcdefg');
        $baoFooWap->setOptions($options);
        $baoFooWap->getVerifyData();
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

        $baoFooWap = new BaoFooWap();
        $baoFooWap->verifyOrderPayment([]);
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

        $options = [
            'MemberID' => '100000178',
            'TerminalID' => '10000001',
            'TransID' => '20140326000000123',
            'ResultDesc' => '01',
            'FactMoney' => '123400',
            'AdditionalInfo' => '',
            'SuccTime' => '20140328100612',
            'Md5Sign' => '1ffa97f00c1ae1550bf5c03d595a9496',
        ];

        $baoFooWap = new BaoFooWap();
        $baoFooWap->setPrivateKey('abcdefg');
        $baoFooWap->setOptions($options);
        $baoFooWap->verifyOrderPayment([]);
    }

    /**
     * 測試返回時缺少簽名
     */
    public function testReturnWithoutMd5Sign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $options = [
            'MemberID' => '100000178',
            'TerminalID' => '10000001',
            'TransID' => '20140326000000123',
            'Result' => '1',
            'ResultDesc' => '01',
            'FactMoney' => '123400',
            'AdditionalInfo' => '',
            'SuccTime' => '20140328100612',
        ];

        $baoFooWap = new BaoFooWap();
        $baoFooWap->setPrivateKey('abcdefg');
        $baoFooWap->setOptions($options);
        $baoFooWap->verifyOrderPayment([]);
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
            'MemberID' => '100000178',
            'TerminalID' => '10000001',
            'TransID' => '20140326000000123',
            'Result' => '1',
            'ResultDesc' => '01',
            'FactMoney' => '123400',
            'AdditionalInfo' => '',
            'SuccTime' => '20140328100612',
            'Md5Sign' => 'c31a6b799ff4fda80fb2b94e8186ccce',
        ];

        $baoFooWap = new BaoFooWap();
        $baoFooWap->setPrivateKey('abcdefg');
        $baoFooWap->setOptions($options);
        $baoFooWap->verifyOrderPayment([]);
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
            'MemberID' => '100000178',
            'TerminalID' => '10000001',
            'TransID' => '20140326000000123',
            'Result' => '0',
            'ResultDesc' => '01',
            'FactMoney' => '123400',
            'AdditionalInfo' => '',
            'SuccTime' => '20140328100612',
            'Md5Sign' => '81de14bb545d46a9ea667ead3a99b3fe',
        ];

        $baoFooWap = new BaoFooWap();
        $baoFooWap->setPrivateKey('abcdefg');
        $baoFooWap->setOptions($options);
        $baoFooWap->verifyOrderPayment([]);
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

        $options = [
            'MemberID' => '100000178',
            'TerminalID' => '10000001',
            'TransID' => '20140326000000123',
            'Result' => '1',
            'ResultDesc' => '0000',
            'FactMoney'=> '123400',
            'AdditionalInfo' => '',
            'SuccTime' => '20140328100612',
            'Md5Sign' => '2191da0056fb4f843dbdd37876ff80e4',
        ];

        $baoFooWap = new BaoFooWap();
        $baoFooWap->setPrivateKey('abcdefg');
        $baoFooWap->setOptions($options);
        $baoFooWap->verifyOrderPayment([]);
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
            'MemberID' => '100000178',
            'TerminalID' => '10000001',
            'TransID' => '20140326000000123',
            'Result' => '1',
            'ResultDesc' => '01',
            'FactMoney' => '123400',
            'AdditionalInfo' => '',
            'SuccTime' => '20140328100612',
            'Md5Sign' => '1ffa97f00c1ae1550bf5c03d595a9496',
        ];

        $entry = ['id' => '20140327000001456'];

        $baoFooWap = new BaoFooWap();
        $baoFooWap->setPrivateKey('abcdefg');
        $baoFooWap->setOptions($options);
        $baoFooWap->verifyOrderPayment($entry);
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
            'MemberID' => '100000178',
            'TerminalID' => '10000001',
            'TransID' => '20140326000000123',
            'Result' => '1',
            'ResultDesc' => '01',
            'FactMoney' => '123400',
            'AdditionalInfo' => '',
            'SuccTime' => '20140328100612',
            'Md5Sign' => '1ffa97f00c1ae1550bf5c03d595a9496',
        ];

        $entry = [
            'id' => '20140326000000123',
            'amount' => '12340',
        ];

        $baoFooWap = new BaoFooWap();
        $baoFooWap->setPrivateKey('abcdefg');
        $baoFooWap->setOptions($options);
        $baoFooWap->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果
     */
    public function testReturnOrder()
    {
        $options = [
            'MemberID' => '100000178',
            'TerminalID' => '10000001',
            'TransID' => '20140326000000123',
            'Result' => '1',
            'ResultDesc' => '01',
            'FactMoney' => '123400',
            'AdditionalInfo' => '',
            'SuccTime' => '20140328100612',
            'Md5Sign' => '1ffa97f00c1ae1550bf5c03d595a9496',
        ];

        $entry = [
            'id' => '20140326000000123',
            'amount' => '1234'
        ];

        $baoFooWap = new BaoFooWap();
        $baoFooWap->setPrivateKey('abcdefg');
        $baoFooWap->setOptions($options);
        $baoFooWap->verifyOrderPayment($entry);

        $this->assertEquals('OK', $baoFooWap->getMsg());
    }
}
