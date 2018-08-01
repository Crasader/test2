<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\CloudIps;

class CloudIpsTest extends DurianTestCase
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

        $cloudIps = new CloudIps();
        $cloudIps->getVerifyData();
    }

    /**
     * 測試加密基本參數設定未指定支付參數
     */
    public function testSetEncodeSourceNoPayParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No pay parameter specified',
            180145
        );

        $cloudIps = new CloudIps();
        $cloudIps->setPrivateKey('pd9igfj3ofjaopj');

        $sourceData = ['number' => ''];

        $cloudIps->setOptions($sourceData);
        $cloudIps->getVerifyData();
    }

    /**
     * 測試加密基本參數設定帶入不支援的銀行
     */
    public function testSetEncodeSourceUnsupportedPaymentVendor()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'PaymentVendor is not supported by PaymentGateway',
            180066
        );

        $cloudIps = new CloudIps();
        $cloudIps->setPrivateKey('pd9igfj3ofjaopj');

        $sourceData = [
            'number' => '100986',
            'orderId' => '201403071109001',
            'amount' => '1234',
            'orderCreateDate' => '201403071109',
            'notify_url' => 'http://pay.1199-eb.net/pay/RequestReturn.php',
            'paymentVendorId' => '999',
            'merchantId' => '12345',
            'domain' => '6',
        ];

        $cloudIps->setOptions($sourceData);
        $cloudIps->getVerifyData();
    }

    /**
     * 測試加密
     */
    public function testSetEncodeSuccess()
    {
        $sourceData = [
            'number' => '100986',
            'orderId' => '201403071109001',
            'amount' => '1234',
            'orderCreateDate' => '201403071109',
            'notify_url' => 'http://esball.org/app/member/pay_online2/pay_result.php',
            'paymentVendorId' => 15, //銀行對應：'15' => '00087', //深圳平安銀行
            'merchantId' => '12345',
            'domain' => '6',
        ];

        $cloudIps = new CloudIps();
        $cloudIps->setPrivateKey('pd9igfj3ofjaopj');
        $cloudIps->setOptions($sourceData);
        $encodeData = $cloudIps->getVerifyData();

        $notifyUrl = sprintf(
            '%s?pay_system=%s&hallid=%s',
            $sourceData['notify_url'],
            $sourceData['merchantId'],
            $sourceData['domain']
        );

        $this->assertEquals('pd9igfj3ofjaopj', $encodeData['Mer_key']);
        $this->assertEquals($sourceData['number'], $encodeData['Mer_code']);
        $this->assertEquals($sourceData['orderId'], $encodeData['Billno']);
        $this->assertRegExp("/^1234.00$/", $encodeData['Amount']);
        $this->assertEquals('20140307', $encodeData['Date']);
        $this->assertEquals($notifyUrl, $encodeData['Merchanturl']);
        $this->assertEquals($notifyUrl, $encodeData['FailUrl']);
        $this->assertEquals('00087', $encodeData['Bankco']);
        $this->assertEquals('0b646ee0302d6271c6b7827cb02ca612', $encodeData['SignMD5']);
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

        $cloudIps = new CloudIps();

        $cloudIps->verifyOrderPayment([]);
    }

    /**
     * 測試解密驗證時沒有支付平台缺少回傳參數(測試amount:金額)
     */
    public function testVerifyWithNoReturnParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $cloudIps = new CloudIps();
        $cloudIps->setPrivateKey('pd9igfj3ofjaopj');

        $sourceData = [
            'billno'        => '201403071109001',
            'Currency_type' => 'RMB',
            'date'          => '20140307',
            'succ'          => 'Y',
            'ipsbillno'     => '201403071211112345',
            'retencodetype' => '17',
            'signature'     => 'e0a0f7db6f10ede7f02e6d152261d7fc'
        ];

        $cloudIps->setOptions($sourceData);
        $cloudIps->verifyOrderPayment([]);
    }

    /**
     * 測試解密驗證時沒有支付平台缺少回傳參數(測試signature:加密簽名)
     */
    public function testVerifyWithoutSignature()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $cloudIps = new CloudIps();
        $cloudIps->setPrivateKey('pd9igfj3ofjaopj');

        $sourceData = [
            'billno'        => '201403071109001',
            'Currency_type' => 'RMB',
            'amount'        => '1234.00',
            'date'          => '20140307',
            'succ'          => 'Y',
            'ipsbillno'     => '201403071211112345',
            'retencodetype' => '17'
        ];

        $cloudIps->setOptions($sourceData);
        $cloudIps->verifyOrderPayment([]);
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

        $cloudIps = new CloudIps();
        $cloudIps->setPrivateKey('pd9igfj3ofjaopj');

        $sourceData = [
            'billno'        => '201403071109001',
            'Currency_type' => 'RMB',
            'amount'        => '12340.00',
            'date'          => '20140307',
            'succ'          => 'Y',
            'ipsbillno'     => '201403071211112345',
            'retencodetype' => '17',
            'signature'     => 'd9d48d3d53405e1ba637c8fb4cf7674c'
        ];

        $cloudIps->setOptions($sourceData);
        $cloudIps->verifyOrderPayment([]);
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

        $cloudIps = new CloudIps();
        $cloudIps->setPrivateKey('pd9igfj3ofjaopj');

        $sourceData = [
            'billno'        => '201403071109001',
            'Currency_type' => 'RMB',
            'amount'        => '12340.00',
            'date'          => '20140307',
            'succ'          => 'N',
            'ipsbillno'     => '201403071211112345',
            'retencodetype' => '17',
            'signature'     => '1e1088985104bedbce48c9ea909315e9'
        ];

        $cloudIps->setOptions($sourceData);
        $cloudIps->verifyOrderPayment([]);
    }

    /**
     * 測試線上支付單號不正確的情況
     */
    public function testOrderIdError()
    {
        $this->setExpectedException('BB\DurianBundle\Exception\PaymentException', 'Order Id error', 180061);

        $cloudIps = new CloudIps();
        $cloudIps->setPrivateKey('pd9igfj3ofjaopj');

        $sourceData = [
            'billno'        => '201403071109001',
            'Currency_type' => 'RMB',
            'amount'        => '12340.00',
            'date'          => '20140307',
            'succ'          => 'Y',
            'ipsbillno'     => '201403071211112345',
            'retencodetype' => '17',
            'signature'     => '1231232fd54d11ac50a5585b8c6af915'
        ];

        $entry = ['id' => '201403071109123'];

        $cloudIps->setOptions($sourceData);
        $cloudIps->verifyOrderPayment($entry);
    }

    /**
     * 測試線上支付金額不正確的情況
     */
    public function testOrderAmountError()
    {
        $this->setExpectedException('BB\DurianBundle\Exception\PaymentException', 'Order Amount error', 180058);

        $cloudIps = new CloudIps();
        $cloudIps->setPrivateKey('pd9igfj3ofjaopj');

        $sourceData = [
            'billno'        => '201403071109001',
            'Currency_type' => 'RMB',
            'amount'        => '12340.00',
            'date'          => '20140307',
            'succ'          => 'Y',
            'ipsbillno'     => '201403071211112345',
            'retencodetype' => '17',
            'signature'     => '1231232fd54d11ac50a5585b8c6af915'
        ];

        $entry = [
            'id' => '201403071109001',
            'amount' => '1234.0000'
        ];

        $cloudIps->setOptions($sourceData);
        $cloudIps->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testPaySuccess()
    {
        $cloudIps = new CloudIps();
        $cloudIps->setPrivateKey('pd9igfj3ofjaopj');

        $sourceData = [
            'billno'        => '201403071109001',
            'Currency_type' => 'RMB',
            'amount'        => '1234.00',
            'date'          => '20140307',
            'succ'          => 'Y',
            'ipsbillno'     => '201403071211112345',
            'retencodetype' => '17',
            'signature'     => 'e0a0f7db6f10ede7f02e6d152261d7fc'
        ];

        $entry = [
            'id' => '201403071109001',
            'amount' => '1234.0000'
        ];

        $cloudIps->setOptions($sourceData);
        $cloudIps->verifyOrderPayment($entry);

        $this->assertEquals('success', $cloudIps->getMsg());
    }
}
