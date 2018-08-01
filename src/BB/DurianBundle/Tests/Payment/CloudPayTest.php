<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\CloudPay;

class CloudPayTest extends DurianTestCase
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

        $cloudPay = new CloudPay();
        $cloudPay->getVerifyData();
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

        $cloudPay = new CloudPay();
        $cloudPay->setPrivateKey('pd98f5q1d5');

        $sourceData = ['number' => ''];

        $cloudPay->setOptions($sourceData);
        $cloudPay->getVerifyData();
    }

    /**
     * 測試加密參數設定成功
     */
    public function testSetEncodeSuccess()
    {
        $sourceData = [
            'number' => '1',
            'orderId' => '1',
            'amount' => '10000',
            'notify_url' => 'http://esball.org/app/member/pay_online2/pay_result.php',
            'username' => 'test',
            'domain' => '6',
            'merchantId' => '12345',
        ];

        $cloudPay = new CloudPay();
        $cloudPay->setPrivateKey('pd98f5q1d5');
        $cloudPay->setOptions($sourceData);
        $verifyData = $cloudPay->getVerifyData();

        $notifyUrl = sprintf(
            '%s?pay_system=%s&hallid=%s',
            $sourceData['notify_url'],
            $sourceData['merchantId'],
            $sourceData['domain']
        );

        $this->assertEquals($sourceData['number'], $verifyData['userID']);
        $this->assertEquals($sourceData['orderId'], $verifyData['orderId']);
        $this->assertEquals($sourceData['amount'], $verifyData['amt']);
        $this->assertEquals($notifyUrl, $verifyData['url']);
        $this->assertEquals($sourceData['username'], $verifyData['name']);
        $this->assertEquals($sourceData['domain'], $verifyData['des']);
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

        $cloudPay = new CloudPay();

        $cloudPay->verifyOrderPayment([]);
    }

    /**
     * 測試解密驗證時沒有支付平台缺少回傳參數(測試amt:金額)
     */
    public function testVerifyWithNoReturnParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $cloudPay = new CloudPay();
        $cloudPay->setPrivateKey('pd98f5q1d5');

        $sourceData = [
            'userID' => '1',
            'orderId' => '20140305123456001',
            'succ' => 'Y',
            'hmac' => '2fbebee4f03638c2d47883b91aacad5f'
        ];

        $cloudPay->setOptions($sourceData);
        $cloudPay->verifyOrderPayment([]);
    }

    /**
     * 測試解密驗證時沒有支付平台缺少回傳參數(測試hmac:加密簽名)
     */
    public function testVerifyWithoutHmac()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $cloudPay = new CloudPay();
        $cloudPay->setPrivateKey('pd98f5q1d5');

        $sourceData = [
            'userID' => '1',
            'orderId' => '20140305123456001',
            'amt' => '10000',
            'succ' => 'Y'
        ];

        $cloudPay->setOptions($sourceData);
        $cloudPay->verifyOrderPayment([]);
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

        $cloudPay = new CloudPay();
        $cloudPay->setPrivateKey('pd98f5q1d5');

        $sourceData = [
            'userID' => '1',
            'orderId' => '20140305123456001',
            'amt' => '10000',
            'succ'  => 'Y',
            'hmac' => '145fba8860702f61a4ef59d62a958a25'
        ];

        $cloudPay->setOptions($sourceData);
        $cloudPay->verifyOrderPayment([]);
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

        $cloudPay = new CloudPay();
        $cloudPay->setPrivateKey('pd98f5q1d5');

        $sourceData = [
            'userID' => '1',
            'orderId' => '20140305123456001',
            'amt' => '10000',
            'succ' => 'N',
            'hmac' => '6252b94dad9ebc997341328b5fa47002'
        ];

        $cloudPay->setOptions($sourceData);
        $cloudPay->verifyOrderPayment([]);
    }

    /**
     * 測試線上支付單號不正確的情況
     */
    public function testOrderIdError()
    {
        $this->setExpectedException('BB\DurianBundle\Exception\PaymentException', 'Order Id error', 180061);

        $cloudPay = new CloudPay();
        $cloudPay->setPrivateKey('pd98f5q1d5');

        $sourceData = [
            'userID' => '1',
            'orderId' => '20140305123456001',
            'amt' => '10000',
            'succ' => 'Y',
            'hmac' => '2fbebee4f03638c2d47883b91aacad5f'
        ];

        $entry = ['id' => '20130212123456007'];

        $cloudPay->setOptions($sourceData);
        $cloudPay->verifyOrderPayment($entry);
    }

    /**
     * 測試線上支付金額不正確的情況
     */
    public function testOrderAmountError()
    {
        $this->setExpectedException('BB\DurianBundle\Exception\PaymentException', 'Order Amount error', 180058);

        $cloudPay = new CloudPay();
        $cloudPay->setPrivateKey('pd98f5q1d5');

        $sourceData = [
            'userID' => '1',
            'orderId' => '20140305123456001',
            'amt' => '10000',
            'succ' => 'Y',
            'hmac' => '2fbebee4f03638c2d47883b91aacad5f'
        ];

        $entry = [
            'id' => '20140305123456001',
            'amount' => '9999.9999'
        ];

        $cloudPay->setOptions($sourceData);
        $cloudPay->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testPaySuccess()
    {
        $cloudPay = new CloudPay();
        $cloudPay->setPrivateKey('pd98f5q1d5');

        $sourceData = [
            'userID' => '1',
            'orderId' => '20140305123456001',
            'amt' => '10000',
            'succ' => 'Y',
            'hmac' => '2fbebee4f03638c2d47883b91aacad5f'
        ];

        $entry = [
            'id' => '20140305123456001',
            'amount' => '10000'
        ];

        $cloudPay->setOptions($sourceData);
        $cloudPay->verifyOrderPayment($entry);

        $this->assertEquals('[success]', $cloudPay->getMsg());
    }
}
