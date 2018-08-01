<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\NewBaoFoo;

class NewBaoFooTest extends DurianTestCase
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

        $newBaoFoo = new NewBaoFoo();
        $newBaoFoo->getVerifyData();
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

        $newBaoFoo = new NewBaoFoo();
        $newBaoFoo->setPrivateKey('abcdefg');

        $sourceData = ['number' => ''];

        $newBaoFoo->setOptions($sourceData);
        $newBaoFoo->getVerifyData();
    }

    /**
     * 測試加密
     */
    public function testGetEncodeData()
    {
        $sourceData = [
            'number' => '100000178',
            'orderCreateDate' => '2014-03-26 12:09:53',
            'orderId' => '20140326000000123',
            'amount' => '1234',
            'notify_url' => 'http://esball.org/app/member/pay_online2/pay_result.php',
            'username' => 'hello123',
            'merchant_extra' => ['terminalId' => '10000001'],
            'merchantId' => '12345',
            'domain' => '6',
        ];

        $newBaoFoo = new NewBaoFoo();
        $newBaoFoo->setPrivateKey('abcdefg');
        $newBaoFoo->setOptions($sourceData);
        $encodeData = $newBaoFoo->getVerifyData();

        $notifyUrl = sprintf(
            '%s?pay_system=%s&hallid=%s',
            $sourceData['notify_url'],
            $sourceData['merchantId'],
            $sourceData['domain']
        );

        $this->assertEquals($sourceData['number'], $encodeData['MemberID']);
        $this->assertEquals('20140326120953', $encodeData['TradeDate']);
        $this->assertEquals($sourceData['orderId'], $encodeData['TransID']);
        $this->assertEquals($sourceData['amount'] * 100, $encodeData['OrderMoney']);
        $this->assertEquals($notifyUrl, $encodeData['PageUrl']);
        $this->assertEquals($notifyUrl, $encodeData['ReturnUrl']);
        $this->assertEquals('3d3326aca5a0b67e503cdabfcec40abf', $encodeData['Signature']);
        $this->assertEquals('hello123', $encodeData['ProductName']);
        $this->assertEquals('hello123', $encodeData['Username']);
    }

    /**
     * 測試加密,找不到商家的terminalId附加設定值
     */
    public function testGetEncodeDataButNoTerminalIdSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No merchant extra value specified',
            180143
        );

        $newBaoFoo = new NewBaoFoo();
        $newBaoFoo->setPrivateKey('abcdefg');

        $sourceData = [
            'number' => '100000178',
            'orderCreateDate' => '2014-03-26 12:09:53',
            'orderId' => '20140326000000123',
            'amount' => '1234',
            'notify_url' => 'http://www.baofoo.com/demo/UserNotIFy.aspx',
            'username' => 'hello123',
            'merchant_extra' => [],
            'merchantId' => '12345',
            'domain' => '6',
        ];

        $newBaoFoo->setOptions($sourceData);
        $newBaoFoo->getVerifyData();
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

        $newBaoFoo = new NewBaoFoo();

        $newBaoFoo->verifyOrderPayment([]);
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

        $newBaoFoo = new NewBaoFoo();
        $newBaoFoo->setPrivateKey('abcdefg');

        $sourceData = [
            'MemberID'       => '100000178',
            'TerminalID'     => '10000001',
            'TransID'        => '20140326000000123',
            'ResultDesc'     => '01',
            'FactMoney'      => '123400',
            'AdditionalInfo' => '',
            'SuccTime'       => '20140328100612',
            'Md5Sign'        => '1ffa97f00c1ae1550bf5c03d595a9496'
        ];

        $newBaoFoo->setOptions($sourceData);
        $newBaoFoo->verifyOrderPayment([]);
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

        $newBaoFoo = new NewBaoFoo();
        $newBaoFoo->setPrivateKey('abcdefg');

        $sourceData = [
            'MemberID'       => '100000178',
            'TerminalID'     => '10000001',
            'TransID'        => '20140326000000123',
            'Result'         => '1',
            'ResultDesc'     => '01',
            'FactMoney'      => '123400',
            'AdditionalInfo' => '',
            'SuccTime'       => '20140328100612'
        ];

        $newBaoFoo->setOptions($sourceData);
        $newBaoFoo->verifyOrderPayment([]);
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

        $newBaoFoo = new NewBaoFoo();
        $newBaoFoo->setPrivateKey('abcdefg');

        $sourceData = [
            'MemberID'       => '100000178',
            'TerminalID'     => '10000001',
            'TransID'        => '20140326000000123',
            'Result'         => '1',
            'ResultDesc'     => '01',
            'FactMoney'      => '123400',
            'AdditionalInfo' => '',
            'SuccTime'       => '20140328100612',
            'Md5Sign'        => 'c31a6b799ff4fda80fb2b94e8186ccce'
        ];

        $newBaoFoo->setOptions($sourceData);
        $newBaoFoo->verifyOrderPayment([]);
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

        $newBaoFoo = new NewBaoFoo();
        $newBaoFoo->setPrivateKey('abcdefg');

        $sourceData = [
            'MemberID'       => '100000178',
            'TerminalID'     => '10000001',
            'TransID'        => '20140326000000123',
            'Result'         => '0',
            'ResultDesc'     => '01',
            'FactMoney'      => '123400',
            'AdditionalInfo' => '',
            'SuccTime'       => '20140328100612',
            'Md5Sign'        => '81de14bb545d46a9ea667ead3a99b3fe'
        ];

        $newBaoFoo->setOptions($sourceData);
        $newBaoFoo->verifyOrderPayment([]);
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

        $newBaoFoo = new NewBaoFoo();
        $newBaoFoo->setPrivateKey('abcdefg');

        $sourceData = [
            'MemberID'       => '100000178',
            'TerminalID'     => '10000001',
            'TransID'        => '20140326000000123',
            'Result'         => '1',
            'ResultDesc'     => '0000',
            'FactMoney'      => '123400',
            'AdditionalInfo' => '',
            'SuccTime'       => '20140328100612',
            'Md5Sign'        => '2191da0056fb4f843dbdd37876ff80e4'
        ];

        $newBaoFoo->setOptions($sourceData);
        $newBaoFoo->verifyOrderPayment([]);
    }

    /**
     * 測試線上支付單號不正確的情況
     */
    public function testOrderIdError()
    {
        $this->setExpectedException('BB\DurianBundle\Exception\PaymentException', 'Order Id error', 180061);

        $newBaoFoo = new NewBaoFoo();
        $newBaoFoo->setPrivateKey('abcdefg');

        $sourceData = [
            'MemberID'       => '100000178',
            'TerminalID'     => '10000001',
            'TransID'        => '20140326000000123',
            'Result'         => '1',
            'ResultDesc'     => '01',
            'FactMoney'      => '123400',
            'AdditionalInfo' => '',
            'SuccTime'       => '20140328100612',
            'Md5Sign'        => '1ffa97f00c1ae1550bf5c03d595a9496'
        ];

        $entry = ['id' => '20140327000001456'];

        $newBaoFoo->setOptions($sourceData);
        $newBaoFoo->verifyOrderPayment($entry);
    }

    /**
     * 測試金額比對錯誤的情況
     */
    public function testAmountFailure()
    {
        $this->setExpectedException('BB\DurianBundle\Exception\PaymentException', 'Order Amount error', 180058);

        $newBaoFoo = new NewBaoFoo();
        $newBaoFoo->setPrivateKey('abcdefg');

        $sourceData = [
            'MemberID'       => '100000178',
            'TerminalID'     => '10000001',
            'TransID'        => '20140326000000123',
            'Result'         => '1',
            'ResultDesc'     => '01',
            'FactMoney'      => '123400',
            'AdditionalInfo' => '',
            'SuccTime'       => '20140328100612',
            'Md5Sign'        => '1ffa97f00c1ae1550bf5c03d595a9496'
        ];

        $entry = [
            'id' => '20140326000000123',
            'amount' => '12340'
        ];

        $newBaoFoo->setOptions($sourceData);
        $newBaoFoo->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testPaySuccess()
    {
        $newBaoFoo = new NewBaoFoo();
        $newBaoFoo->setPrivateKey('abcdefg');

        $sourceData = [
            'MemberID'       => '100000178',
            'TerminalID'     => '10000001',
            'TransID'        => '20140326000000123',
            'Result'         => '1',
            'ResultDesc'     => '01',
            'FactMoney'      => '123400',
            'AdditionalInfo' => '',
            'SuccTime'       => '20140328100612',
            'Md5Sign'        => '1ffa97f00c1ae1550bf5c03d595a9496'
        ];

        $entry = [
            'id' => '20140326000000123',
            'amount' => '1234'
        ];

        $newBaoFoo->setOptions($sourceData);
        $newBaoFoo->verifyOrderPayment($entry);

        $this->assertEquals('OK', $newBaoFoo->getMsg());
    }
}
