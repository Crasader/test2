<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\Weishih;

class WeishihTest extends DurianTestCase
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

        $weishih = new Weishih();
        $weishih->getVerifyData();
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

        $weishih = new Weishih();
        $weishih->setPrivateKey('5bcb841b696618a89a8b9a1faab9f102');

        $sourceData = ['number' => ''];

        $weishih->setOptions($sourceData);
        $weishih->getVerifyData();
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

        $weishih = new Weishih();
        $weishih->setPrivateKey('5bcb841b696618a89a8b9a1faab9f102');

        $sourceData = [
            'number' => '411419157065431',
            'orderId' => '201403200000000123',
            'amount' => '1234.56',
            'notify_url' => 'http://pay.1199-eb.net/pay/RequestReturn.php',
            'username' => 'acctest',
            'paymentVendorId' => '999',
            'merchantId' => '12345',
            'domain' => '6',
        ];

        $weishih->setOptions($sourceData);
        $weishih->getVerifyData();
    }

    /**
     * 測試加密參數設定成功
     */
    public function testSetEncodeSuccess()
    {
        $sourceData = [
            'number' => '411419157065431',
            'orderId' => '201403200000000123',
            'amount' => '1234.56',
            'notify_url' => 'http://esball.org/app/member/pay_online2/pay_result.php',
            'username' => 'acctest',
            'paymentVendorId' => '234', //234 => 'X'
            'merchantId' => '12345',
            'domain' => '6',
        ];

        $weishih = new Weishih();
        $weishih->setPrivateKey('5bcb841b696618a89a8b9a1faab9f102');
        $weishih->setOptions($sourceData);
        $verifyData = $weishih->getVerifyData();

        $notifyUrl = sprintf(
            '%s?pay_system=%s&hallid=%s',
            $sourceData['notify_url'],
            $sourceData['merchantId'],
            $sourceData['domain']
        );

        $this->assertEquals($sourceData['number'], $verifyData['userID']);
        $this->assertEquals($sourceData['orderId'], $verifyData['orderId']);
        $this->assertSame(1234.56, $verifyData['amt']);
        $this->assertEquals($notifyUrl, $verifyData['url']);
        $this->assertEquals($sourceData['username'], $verifyData['name']);
        $this->assertEquals('X', $verifyData['bank']);
        $this->assertEquals('c49c15762fe90be49073841cc42b533c', $verifyData['hmac']);
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

        $weishih = new Weishih();

        $weishih->verifyOrderPayment([]);
    }

    /**
     * 測試解密驗證時沒有支付平台未指定返回參數
     */
    public function testVerifyWithNoReturnParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $weishih = new Weishih();
        $weishih->setPrivateKey('b63a730cb878a1cd24cff17902e8d46e');

        $sourceData = [
            'userID' => '411419157065431',
            'orderId' => '201403200000000123',
            'amt' => '1234.56',
            'hmac2' => 'b63a730cb878a1cd24cff17902e8d46e'
        ];

        $weishih->setOptions($sourceData);
        $weishih->verifyOrderPayment([]);
    }

    /**
     * 測試解密驗證時沒有支付平台缺少回傳參數(測試hmac2:加密簽名)
     */
    public function testVerifyWithoutHmac2()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $weishih = new Weishih();
        $weishih->setPrivateKey('5bcb841b696618a89a8b9a1faab9f102');

        $sourceData = [
            'userID' => '411419157065431',
            'orderId' => '201403200000000123',
            'amt' => '1234.56',
            'succ' => 'Y'
        ];

        $weishih->setOptions($sourceData);
        $weishih->verifyOrderPayment([]);
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

        $weishih = new Weishih();
        $weishih->setPrivateKey('5bcb841b696618a89a8b9a1faab9f102');

        $sourceData = [
            'userID' => '411419157065431',
            'orderId' => '201403200000000123',
            'amt' => '1234.56',
            'succ' => 'Y',
            'hmac2' => '145fba8860702f61a4ef59d62a958a25'
        ];

        $weishih->setOptions($sourceData);
        $weishih->verifyOrderPayment([]);
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

        $weishih = new Weishih();
        $weishih->setPrivateKey('5bcb841b696618a89a8b9a1faab9f102');

        $sourceData = [
            'userID' => '411419157065431',
            'orderId' => '201403200000000123',
            'amt' => '1234.56',
            'succ' => 'N',
            'hmac2' => '814e68074b6bd38dac39c320c2a43b47'
        ];

        $weishih->setOptions($sourceData);
        $weishih->verifyOrderPayment([]);
    }

    /**
     * 測試線上支付單號不正確的情況
     */
    public function testOrderIdError()
    {
        $this->setExpectedException('BB\DurianBundle\Exception\PaymentException', 'Order Id error', 180061);

        $weishih = new Weishih();
        $weishih->setPrivateKey('5bcb841b696618a89a8b9a1faab9f102');

        $sourceData = [
            'userID' => '411419157065431',
            'orderId' => '201403200000000123',
            'amt' => '1234.56',
            'succ' => 'Y',
            'hmac2' => 'b63a730cb878a1cd24cff17902e8d46e'
        ];

        $entry = ['id' => '20140320000000012'];

        $weishih->setOptions($sourceData);
        $weishih->verifyOrderPayment($entry);
    }

    /**
     * 測試線上支付金額不正確的情況
     */
    public function testOrderAmountError()
    {
        $this->setExpectedException('BB\DurianBundle\Exception\PaymentException', 'Order Amount error', 180058);

        $weishih = new Weishih();
        $weishih->setPrivateKey('5bcb841b696618a89a8b9a1faab9f102');

        $sourceData = [
            'userID' => '411419157065431',
            'orderId' => '201403200000000123',
            'amt' => '1234.56',
            'succ' => 'Y',
            'hmac2' => 'b63a730cb878a1cd24cff17902e8d46e'
        ];

        $entry = [
            'id' => '201403200000000123',
            'amount' => '12345.6'
        ];

        $weishih->setOptions($sourceData);
        $weishih->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testPaySuccess()
    {
        $weishih = new Weishih();
        $weishih->setPrivateKey('5bcb841b696618a89a8b9a1faab9f102');

        $sourceData = [
            'userID' => '411419157065431',
            'orderId' => '201403200000000123',
            'amt' => '1234.56',
            'succ' => 'Y',
            'hmac2' => 'b63a730cb878a1cd24cff17902e8d46e'
        ];

        $entry = [
            'id' => '201403200000000123',
            'amount' => '1234.56'
        ];

        $weishih->setOptions($sourceData);
        $weishih->verifyOrderPayment($entry);

        $this->assertEquals('[success]', $weishih->getMsg());
    }
}
