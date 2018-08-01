<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\Payza;

class PayzaTest extends DurianTestCase
{
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

        $payza = new Payza();

        $sourceData = ['number' => ''];

        $payza->setOptions($sourceData);
        $payza->getVerifyData();
    }

    /**
     * 測試加密
     */
    public function testEncode()
    {
        $payza = new Payza();

        $sourceData = [
            'number' => 'acctest@gmail.com',
            'ap_purchasetype' => 'item',
            'orderId' => '201410310000000013',
            'amount' => '5',
            'ap_currency' => 'USD'
        ];

        $payza->setOptions($sourceData);
        $encodeData = $payza->getVerifyData();

        $this->assertEquals($sourceData['number'], $encodeData['ap_merchant']);
        $this->assertEquals($sourceData['ap_purchasetype'], $encodeData['ap_purchasetype']);
        $this->assertEquals($sourceData['orderId'], $encodeData['ap_itemname']);
        $this->assertEquals($sourceData['amount'], $encodeData['ap_amount']);
        $this->assertEquals($sourceData['ap_currency'], $encodeData['ap_currency']);
    }

    /**
     * 測試解密驗證沒有帶入必要的參數(測試key)
     */
    public function testVerifyWithoutKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $payza = new Payza();
        $payza->verifyOrderPayment([]);
    }

    /**
     * 測試解密驗證未指定返回參數
     */
    public function testVerifyWithNoReturnParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $payza = new Payza();
        $payza->setPrivateKey('Hc33KnJHiR7lAx7z');
        $payza->verifyOrderPayment([]);
    }

    /**
     * 測試返回結果為測試模式
     */
    public function testReturnIsTestMode()
    {
        $msg = 'Test mode is enabled, please turn off test mode and try again later';

        $this->setExpectedException('BB\DurianBundle\Exception\PaymentConnectionException', $msg, 180084);

        $payza = new Payza();
        $payza->setPrivateKey('Hc33KnJHiR7lAx7z');

        $sourceData = [
            'ap_merchant' => 'acctest@gmail.com',
            'ap_securitycode' => 'Hc33KnJHiR7lAx7z',
            'ap_status' => 'Success',
            'ap_referencenumber' => 'SB-B0910-04141-E2067',
            'ap_amount' => '5.00',
            'ap_currency' => 'USD',
            'ap_test' => '1',
            'ap_itemname' => '201410310000000013'
        ];

        $payza->setOptions($sourceData);
        $payza->verifyOrderPayment([]);
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

        $payza = new Payza();
        $payza->setPrivateKey('Hc33KnJHiR7lAx7z');

        $sourceData = [
            'ap_merchant' => 'acctest@gmail.com',
            'ap_securitycode' => 'Hc33KnJHiR7lAx7z',
            'ap_status' => 'Failure',
            'ap_referencenumber' => 'SB-B0910-04141-E2067',
            'ap_amount' => '5.00',
            'ap_currency' => 'USD',
            'ap_test' => '0',
            'ap_itemname' => '201410310000000013'
        ];

        $payza->setOptions($sourceData);
        $payza->verifyOrderPayment([]);
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

        $payza = new Payza();
        $payza->setPrivateKey('Hc33KnJHiR7lAx7z');

        $sourceData = [
            'ap_merchant' => 'acctest@gmail.com',
            'ap_securitycode' => 'Hc33KnJHiR7l1234',
            'ap_status' => 'Success',
            'ap_referencenumber' => 'SB-B0910-04141-E2067',
            'ap_amount' => '5.00',
            'ap_currency' => 'USD',
            'ap_test' => '0',
            'ap_itemname' => '201410310000000013'
        ];

        $payza->setOptions($sourceData);
        $payza->verifyOrderPayment([]);
    }

    /**
     * 測試返回時代入支付平台商號錯誤
     */
    public function testReturnPaymentGatewayMerchantError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'PaymentGateway error, Illegal merchant number',
            180082
        );

        $payza = new Payza();
        $payza->setPrivateKey('Hc33KnJHiR7lAx7z');

        $sourceData = [
            'ap_merchant' => 'acctest2@gmail.com',
            'ap_securitycode' => 'Hc33KnJHiR7lAx7z',
            'ap_status' => 'Success',
            'ap_referencenumber' => 'SB-B0910-04141-E2067',
            'ap_amount' => '5.00',
            'ap_currency' => 'USD',
            'ap_test' => '0',
            'ap_itemname' => '201410310000000013',
            'merchant_number' => ''
        ];

        $entry = ['merchant_number' => 'acctest@gmail.com'];

        $payza->setOptions($sourceData);
        $payza->verifyOrderPayment($entry);
    }

    /**
     * 測試返回時帶入幣別不合法
     */
    public function testReturnIllegalOrderCurrency()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Illegal Order currency',
            180083
        );

        $payza = new Payza();
        $payza->setPrivateKey('Hc33KnJHiR7lAx7z');

        $sourceData = [
            'ap_merchant' => 'acctest@gmail.com',
            'ap_securitycode' => 'Hc33KnJHiR7lAx7z',
            'ap_status' => 'Success',
            'ap_referencenumber' => 'SB-B0910-04141-E2067',
            'ap_amount' => '5.00',
            'ap_currency' => 'CNY',
            'ap_test' => '0',
            'ap_itemname' => '201410310000000013'
        ];

        $entry = [
            'merchant_number' => 'acctest@gmail.com',
            'id' => '201410310000000013',
            'amount' => '5.00'
        ];

        $payza->setOptions($sourceData);
        $payza->verifyOrderPayment($entry);
    }

    /**
     * 測試解密驗證訂單號錯誤的情況
     */
    public function testVerifyWithOrderIdError()
    {
        $this->setExpectedException('BB\DurianBundle\Exception\PaymentException', 'Order Id error', 180061);

        $payza = new Payza();
        $payza->setPrivateKey('Hc33KnJHiR7lAx7z');

        $sourceData = [
            'ap_merchant' => 'acctest@gmail.com',
            'ap_securitycode' => 'Hc33KnJHiR7lAx7z',
            'ap_status' => 'Success',
            'ap_referencenumber' => 'SB-B0910-04141-E2067',
            'ap_amount' => '5.00',
            'ap_currency' => 'USD',
            'ap_test' => '0',
            'ap_itemname' => '201410310000000014'
        ];

        $entry = [
            'merchant_number' => 'acctest@gmail.com',
            'id' => '201410310000000013',
            'amount' => '5.00'
        ];

        $payza->setOptions($sourceData);
        $payza->verifyOrderPayment($entry);
    }

    /**
     * 測試解密驗證訂單金額錯誤的情況
     */
    public function testVerifyWithAmountError()
    {
        $this->setExpectedException('BB\DurianBundle\Exception\PaymentException', 'Order Amount error', 180058);

        $payza = new Payza();
        $payza->setPrivateKey('Hc33KnJHiR7lAx7z');

        $sourceData = [
            'ap_merchant' => 'acctest@gmail.com',
            'ap_securitycode' => 'Hc33KnJHiR7lAx7z',
            'ap_status' => 'Success',
            'ap_referencenumber' => 'SB-B0910-04141-E2067',
            'ap_amount' => '1.00',
            'ap_currency' => 'USD',
            'ap_test' => '0',
            'ap_itemname' => '201410310000000013'
        ];

        $entry = [
            'merchant_number' => 'acctest@gmail.com',
            'id' => '201410310000000013',
            'amount' => '5.00'
        ];

        $payza->setOptions($sourceData);
        $payza->verifyOrderPayment($entry);
    }

    /**
     * 測試解密驗證成功
     */
    public function testVerifySuccess()
    {
        $payza = new Payza();
        $payza->setPrivateKey('Hc33KnJHiR7lAx7z');

        $sourceData = [
            'ap_merchant' => 'acctest@gmail.com',
            'ap_securitycode' => 'Hc33KnJHiR7lAx7z',
            'ap_status' => 'Success',
            'ap_referencenumber' => 'SB-B0910-04141-E2067',
            'ap_amount' => '5.00',
            'ap_currency' => 'USD',
            'ap_test' => '0',
            'ap_itemname' => '201410310000000013'
        ];

        $entry = [
            'merchant_number' => 'acctest@gmail.com',
            'id' => '201410310000000013',
            'amount' => '5.00'
        ];

        $payza->setOptions($sourceData);
        $payza->verifyOrderPayment($entry);

        $this->assertEquals('success', $payza->getMsg());
    }
}
