<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\NewEcpss;

class NewEcpssTest extends DurianTestCase
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

        $newEcpss = new NewEcpss();
        $newEcpss->getVerifyData();
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

        $newEcpss = new NewEcpss();
        $newEcpss->setPrivateKey('test');
        $newEcpss->getVerifyData();
    }

    /**
     * 測試支付基本參數設定帶入不支援的銀行
     */
    public function testPayUnsupportedPaymentVendor()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'PaymentVendor is not supported by PaymentGateway',
            180066
        );

        $sourceData = [
            'number' => '40328',
            'orderId' => '201609090000004242',
            'amount' => '0.01',
            'notify_url' => 'http://two123.comxa.com/',
            'orderCreateDate' => '2014-04-24 18:18:59',
            'paymentVendorId' => '999',
            'merchantId' => '12345',
            'domain' => '6',
        ];

        $newEcpss = new NewEcpss();
        $newEcpss->setPrivateKey('test');
        $newEcpss->setOptions($sourceData);
        $newEcpss->getVerifyData();
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $sourceData = [
            'number' => '40328',
            'orderId' => '201609090000004242',
            'amount' => '0.01',
            'notify_url' => 'http://two123.comxa.com/',
            'orderCreateDate' => '2014-04-24 18:18:59',
            'paymentVendorId' => '1',
            'merchantId' => '12345',
            'domain' => '6',
        ];

        $newEcpss = new NewEcpss();
        $newEcpss->setPrivateKey('test');
        $newEcpss->setOptions($sourceData);
        $encodeData = $newEcpss->getVerifyData();

        $notifyUrl = sprintf(
            '%s?pay_system=%s&hallid=%s',
            $sourceData['notify_url'],
            $sourceData['merchantId'],
            $sourceData['domain']
        );

        $this->assertEquals($sourceData['number'], $encodeData['MerNo']);
        $this->assertEquals('20140424181859', $encodeData['OrderTime']);
        $this->assertEquals($sourceData['orderId'], $encodeData['BillNo']);
        $this->assertSame('0.01', $encodeData['Amount']);
        $this->assertEquals($notifyUrl, $encodeData['ReturnURL']);
        $this->assertEquals($notifyUrl, $encodeData['AdviceURL']);
        $this->assertEquals('ICBC', $encodeData['defaultBankNumber']);
        $this->assertEquals('B2CDebit', $encodeData['payType']);
        $this->assertEquals('2D5CAF69F692A77D982B4B90B513BB32', $encodeData['SignInfo']);
    }

    /**
     * 測試支付(銀聯無卡)
     */
    public function testPayWithNoCard()
    {
        $sourceData = [
            'number' => '40328',
            'orderId' => '201609090000004242',
            'amount' => '0.01',
            'notify_url' => 'http://two123.comxa.com/',
            'orderCreateDate' => '2014-04-24 18:18:59',
            'paymentVendorId' => '1093',
            'merchantId' => '12345',
            'domain' => '6',
        ];

        $newEcpss = new NewEcpss();
        $newEcpss->setPrivateKey('test');
        $newEcpss->setOptions($sourceData);
        $encodeData = $newEcpss->getVerifyData();

        $notifyUrl = sprintf(
            '%s?pay_system=%s&hallid=%s',
            $sourceData['notify_url'],
            $sourceData['merchantId'],
            $sourceData['domain']
        );

        $this->assertEquals($sourceData['number'], $encodeData['MerNo']);
        $this->assertEquals('20140424181859', $encodeData['OrderTime']);
        $this->assertEquals($sourceData['orderId'], $encodeData['BillNo']);
        $this->assertSame('0.01', $encodeData['Amount']);
        $this->assertEquals($notifyUrl, $encodeData['ReturnURL']);
        $this->assertEquals($notifyUrl, $encodeData['AdviceURL']);
        $this->assertEquals('NOCARD', $encodeData['defaultBankNumber']);
        $this->assertEquals('noCard', $encodeData['payType']);
        $this->assertEquals('2D5CAF69F692A77D982B4B90B513BB32', $encodeData['SignInfo']);
    }

    /**
     * 測試支付(銀聯在線)
     */
    public function testPayWithUnionpay()
    {
        $sourceData = [
            'number' => '40328',
            'orderId' => '201609090000004242',
            'amount' => '0.01',
            'notify_url' => 'http://two123.comxa.com/',
            'orderCreateDate' => '2014-04-24 18:18:59',
            'paymentVendorId' => '1088',
            'merchantId' => '12345',
            'domain' => '6',
        ];

        $newEcpss = new NewEcpss();
        $newEcpss->setPrivateKey('test');
        $newEcpss->setOptions($sourceData);
        $encodeData = $newEcpss->getVerifyData();

        $notifyUrl = sprintf(
            '%s?pay_system=%s&hallid=%s',
            $sourceData['notify_url'],
            $sourceData['merchantId'],
            $sourceData['domain']
        );

        $this->assertEquals($sourceData['number'], $encodeData['MerNo']);
        $this->assertEquals('20140424181859', $encodeData['OrderTime']);
        $this->assertEquals($sourceData['orderId'], $encodeData['BillNo']);
        $this->assertSame('0.01', $encodeData['Amount']);
        $this->assertEquals($notifyUrl, $encodeData['ReturnURL']);
        $this->assertEquals($notifyUrl, $encodeData['AdviceURL']);
        $this->assertEquals('OTHERS', $encodeData['defaultBankNumber']);
        $this->assertEquals('', $encodeData['payType']);
        $this->assertEquals('2D5CAF69F692A77D982B4B90B513BB32', $encodeData['SignInfo']);
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

        $newEcpss = new NewEcpss();

        $newEcpss->verifyOrderPayment([]);
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

        $newEcpss = new NewEcpss();
        $newEcpss->setPrivateKey('test');
        $newEcpss->verifyOrderPayment([]);
    }

    /**
     *  測試返回時缺少SignInfo
     */
    public function testReturnWithoutSignInfo()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $sourceData = [
            'BillNo' => '201609090000004242',
            'MerNo' => '40328',
            'Amount' => '0.01',
            'Succeed' => '88',
            'OrderNo' => '0000204733',
            'Result' => 'Success',
        ];

        $newEcpss = new NewEcpss();
        $newEcpss->setPrivateKey('test');
        $newEcpss->setOptions($sourceData);
        $newEcpss->verifyOrderPayment([]);
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
            'BillNo' => '201609090000004242',
            'MerNo' => '40328',
            'Amount' => '0.01',
            'Succeed' => '88',
            'OrderNo' => '0000204733',
            'Result' => 'Success',
            'SignInfo' => '550D9D92C7EC5E77FE6693683D485CF7',
        ];

        $newEcpss = new NewEcpss();
        $newEcpss->setPrivateKey('test');
        $newEcpss->setOptions($sourceData);
        $newEcpss->verifyOrderPayment([]);
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
            'BillNo' => '201609090000004242',
            'MerNo' => '40328',
            'Amount' => '0.01',
            'Succeed' => '0',
            'OrderNo' => '0000204733',
            'Result' => 'Fail',
            'SignInfo' => 'A51418CBF5EB91C913A6D01D261523FE',
        ];

        $newEcpss = new NewEcpss();
        $newEcpss->setPrivateKey('test');
        $newEcpss->setOptions($sourceData);
        $newEcpss->verifyOrderPayment([]);
    }

    /**
     * 試返回結果為訂單號錯誤
     */
    public function testReturnOrderIdError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $sourceData = [
            'BillNo' => '201609090000004242',
            'MerNo' => '40328',
            'Amount' => '0.01',
            'Succeed' => '88',
            'OrderNo' => '0000204733',
            'Result' => 'Success',
            'SignInfo' => '4095028C9C7611115EB13327FD5D1C91',
        ];

        $entry = ['id' => '20140103000123456'];

        $newEcpss = new NewEcpss();
        $newEcpss->setPrivateKey('test');
        $newEcpss->setOptions($sourceData);
        $newEcpss->verifyOrderPayment($entry);
    }

    /**
     * 測試金額比對錯誤的情況
     */
    public function testAmountFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $sourceData = [
            'BillNo' => '201609090000004242',
            'MerNo' => '40328',
            'Amount' => '0.01',
            'Succeed' => '88',
            'OrderNo' => '0000204733',
            'Result' => 'Success',
            'SignInfo' => '4095028C9C7611115EB13327FD5D1C91',
        ];

        $entry = [
            'id' => '201609090000004242',
            'amount' => '1.0000',
        ];

        $newEcpss = new NewEcpss();
        $newEcpss->setPrivateKey('test');
        $newEcpss->setOptions($sourceData);
        $newEcpss->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testPaySuccess()
    {
        $sourceData = [
            'BillNo' => '201609090000004242',
            'MerNo' => '40328',
            'Amount' => '0.01',
            'Succeed' => '88',
            'OrderNo' => '0000204733',
            'Result' => 'Success',
            'SignInfo' => '550D9D92C7EC5E77FE6693683D485CF7',
        ];

        $entry = [
            'id' => '201609090000004242',
            'amount' => '0.0100'
        ];

        $newEcpss = new NewEcpss();
        $newEcpss->setPrivateKey('AES2873ggYU23');
        $newEcpss->setOptions($sourceData);
        $newEcpss->verifyOrderPayment($entry);

        $this->assertEquals('ok', $newEcpss->getMsg());
    }
}
