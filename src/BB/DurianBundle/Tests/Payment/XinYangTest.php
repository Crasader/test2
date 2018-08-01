<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\XinYang;

class XinYangTest extends DurianTestCase
{
    /**
     * 測試加密時沒有帶入privateKey的情況
     */
    public function testEncodeWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $xinYang = new XinYang();
        $xinYang->getVerifyData();
    }

    /**
     * 測試加密時未指定支付參數
     */
    public function testEncodeWithNoPayParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No pay parameter specified',
            180145
        );

        $xinYang = new XinYang();
        $xinYang->setPrivateKey('99bill0123456789');

        $sourceData = ['number' => ''];

        $xinYang->setOptions($sourceData);
        $xinYang->getVerifyData();
    }

    /**
     * 測試加密時notify_url格式不合法
     */
    public function testEncodeNotifyUrlInvalid()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Invalid notify_url',
            180146
        );

        $xinYang = new XinYang();
        $xinYang->setPrivateKey('99bill0123456789');

        $sourceData = [
            'number' => '000889904120992',
            'orderId' => '20140314155536001',
            'amount' => '2321',
            'notify_url' => 'http://59.126.84.197:3333?pay_system=10032&hallid=6',
            'paymentVendorId' => '1',
        ];

        $xinYang->setOptions($sourceData);
        $xinYang->getVerifyData();
    }

    /**
     * 測試加密時帶入不支援的銀行
     */
    public function testEncodeWithoutSupportBank()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'PaymentVendor is not supported by PaymentGateway',
            180066
        );

        $xinYang = new XinYang();
        $xinYang->setPrivateKey('99bill0123456789');

        $sourceData = [
            'number' => '000000000000067',
            'orderId' => '2014040200000011',
            'amount' => '0.01',
            'notify_url' => 'http://59.126.84.197:3030',
            'paymentVendorId' => '999',
            'merchantId' => 10032,
            'domain' => 6,
        ];

        $xinYang->setOptions($sourceData);
        $xinYang->getVerifyData();
    }

    /**
     * 測試加密
     */
    public function testSetEncode()
    {
        $sourceData = [
            'number' => '000000000000067',
            'orderId' => '20140616000000101',
            'amount' => '0.01',
            'notify_url' => 'http://esball.org/app/member/pay_online2/pay_result.php',
            'paymentVendorId' => '1',
            'merchantId' => '49851',
            'domain' => '6',
        ];

        $xinYang = new XinYang();
        $xinYang->setPrivateKey('1234567890123451');
        $xinYang->setOptions($sourceData);
        $encodeData = $xinYang->getVerifyData();

        $this->assertEquals($sourceData['number'], $encodeData['merchantid']);
        $this->assertEquals($sourceData['orderId'], $encodeData['orderno']);
        $this->assertSame('0.01', $encodeData['amount']);
        $this->assertEquals($sourceData['notify_url'], $encodeData['merchant_url']);
        $this->assertEquals('', $encodeData['pgupUrl']);
        $this->assertEquals('icbc', $encodeData['bankid']);
        $this->assertEquals('49851_6', $encodeData['note']);
        $this->assertEquals('3AC316352B6DEACA4079DF11893CCF0F', $encodeData['mac']);
    }

    /**
     * 測試解密基本參數設定沒有帶入key的情況
     */
    public function testDecodeWithoutKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $xinYang = new XinYang();

        $xinYang->verifyOrderPayment([]);
    }

    /**
     * 測試解密驗證時支付平台未指定返回參數
     */
    public function testDecodeWithNoReturnParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $xinYang = new XinYang();
        $xinYang->setPrivateKey('1234567890123451');

        $sourceData = [
            'merchantid'     => '000000000000067',
            'orderno'        => '2014040200000011',
            'amount'         => '0.01',
            'transtype'      => '1',
            'date'           => '20140403140308',
            'merchant_param' => '10032_6',
            'mac'            => '01E87D23E2EB7B2421B8E9B50E2B1A04'
        ];

        $xinYang->setOptions($sourceData);
        $xinYang->verifyOrderPayment([]);
    }

    /**
     * 測試解密驗證時支付平台缺少回傳參數(測試mac:加密簽名)
     */
    public function testDecodeWithoutMac()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $xinYang = new XinYang();
        $xinYang->setPrivateKey('1234567890123451');

        $sourceData = [
            'merchantid'     => '000000000000067',
            'orderno'        => '2014040200000011',
            'amount'         => '0.01',
            'transtype'      => '1',
            'date'           => '20140403140308',
            'merchant_param' => '10032_6',
            'succeed'        => 'Y'
        ];

        $xinYang->setOptions($sourceData);
        $xinYang->verifyOrderPayment([]);
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

        $xinYang = new XinYang();
        $xinYang->setPrivateKey('99bill0123456789');

        $sourceData = [
            'merchantid'     => '000889904120992',
            'orderno'        => '20140314155536001',
            'amount'         => '2321.00',
            'transtype'      => '1',
            'date'           => '20140314',
            'merchant_param' => '',
            'succeed'        => 'Y',
            'mac'            => 'd9d48d3d53405e1ba637c8fb4cf7674c'
        ];

        $xinYang->setOptions($sourceData);
        $xinYang->verifyOrderPayment([]);
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

        $xinYang = new XinYang();
        $xinYang->setPrivateKey('99bill0123456789');

        $sourceData = [
            'merchantid'     => '000889904120992',
            'orderno'        => '20140314155536001',
            'amount'         => '2321.00',
            'transtype'      => '1',
            'date'           => '20140314',
            'merchant_param' => '',
            'succeed'        => 'N',
            'mac'            => 'F7EBC876602623EB6DE87EF025100967'
        ];

        $xinYang->setOptions($sourceData);
        $xinYang->verifyOrderPayment([]);
    }

    /**
     * 測試線上支付單號不正確的情況
     */
    public function testOrderIdError()
    {
        $this->setExpectedException('BB\DurianBundle\Exception\PaymentException', 'Order Id error', 180061);

        $xinYang = new XinYang();
        $xinYang->setPrivateKey('99bill0123456789');

        $sourceData = [
            'merchantid'     => '000889904120992',
            'orderno'        => '20140314155536001',
            'amount'         => '2321.00',
            'transtype'      => '1',
            'date'           => '20140314',
            'merchant_param' => '',
            'succeed'        => 'Y',
            'mac'            => '9CDE6923C340B9E64C26CB117EA9CFD3'
        ];

        $entry = ['id' => '20140314155536003'];

        $xinYang->setOptions($sourceData);
        $xinYang->verifyOrderPayment($entry);
    }

    /**
     * 測試線上支付金額不正確的情況
     */
    public function testOrderAmountError()
    {
        $this->setExpectedException('BB\DurianBundle\Exception\PaymentException', 'Order Amount error', 180058);

        $xinYang = new XinYang();
        $xinYang->setPrivateKey('99bill0123456789');

        $sourceData = [
            'merchantid'     => '000889904120992',
            'orderno'        => '20140314155536001',
            'amount'         => '2321.00',
            'transtype'      => '1',
            'date'           => '20140314',
            'merchant_param' => '',
            'succeed'        => 'Y',
            'mac'            => '9CDE6923C340B9E64C26CB117EA9CFD3'
        ];

        $entry = [
            'id' => '20140314155536001',
            'amount' => '1234.00'
        ];

        $xinYang->setOptions($sourceData);
        $xinYang->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testPaySuccess()
    {
        $xinYang = new XinYang();
        $xinYang->setPrivateKey('1234567890123451');

        $sourceData = [
            'transtype'     => '1',
            'amount'        => '0.01',
            'succeed'       => 'Y',
            'merchant_para' => '49851_6',
            'orderno'       => '20140616000000101',
            'mac'           => '1F2585D02781017DE0481C36FDC62E0F',
            'date'          => '20140616',
            'merchantid'    => '000000000000067'
        ];

        $entry = [
            'id' => '20140616000000101',
            'amount' => '0.0100'
        ];

        $xinYang->setOptions($sourceData);
        $xinYang->verifyOrderPayment($entry);

        $this->assertEquals('OK', $xinYang->getMsg());
    }
}
