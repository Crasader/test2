<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\Cldinpay;

class CldinpayTest extends DurianTestCase
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

        $cldinpay = new Cldinpay();
        $cldinpay->getVerifyData();
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

        $cldinpay = new Cldinpay();
        $cldinpay->setPrivateKey('1234');

        $sourceData = ['number' => ''];

        $cldinpay->setOptions($sourceData);
        $cldinpay->getVerifyData();
    }

    /**
     * 測試加密
     */
    public function testGetEncodeData()
    {
        $sourceData = [
            'number' => '0002900F0006944',
            'orderId' => '11032302065863805732',
            'amount' => '10',
            'notify_url' => 'http://esball.org/app/member/pay_online2/pay_result.php',
            'domain' => '1',
            'username' => 'test',
            'merchantId' => '12345',
        ];

        $cldinpay = new Cldinpay();
        $cldinpay->setPrivateKey('1234');
        $cldinpay->setOptions($sourceData);
        $encodeData = $cldinpay->getVerifyData();

        $notifyUrl = sprintf(
            '%s?pay_system=%s&hallid=%s',
            $sourceData['notify_url'],
            $sourceData['merchantId'],
            $sourceData['domain']
        );

        $this->assertEquals($sourceData['number'], $encodeData['userID']);
        $this->assertEquals($sourceData['orderId'], $encodeData['orderId']);
        $this->assertEquals('10', $encodeData['amt']);
        $this->assertEquals($notifyUrl, $encodeData['url']);
        $this->assertEquals($sourceData['domain'], $encodeData['des']);
        $this->assertEquals($sourceData['username'], $encodeData['name']);
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

        $cldinpay = new Cldinpay();

        $cldinpay->verifyOrderPayment([]);
    }

    /**
     * 測試解密驗證時沒有支付平台缺少回傳參數(測試amt:金額)
     */
    public function testVerifyNoReturnParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $cldinpay = new Cldinpay();
        $cldinpay->setPrivateKey('1234');

        $sourceData = [
            'userID' => '2000',
            'orderId' => '20140423000001234',
            'succ' => 'Y',
            'hmac2' => '177c78a865e6d0d93033c7967a83917e'
        ];

        $cldinpay->setOptions($sourceData);
        $cldinpay->verifyOrderPayment([]);
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

        $cldinpay = new Cldinpay();
        $cldinpay->setPrivateKey('1234');

        $sourceData = [
            'userID' => '2000',
            'orderId' => '20140423000001234',
            'amt' => '10',
            'succ' => 'Y'
        ];

        $cldinpay->setOptions($sourceData);
        $cldinpay->verifyOrderPayment([]);
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

        $cldinpay = new Cldinpay();
        $cldinpay->setPrivateKey('1234');

        $sourceData = [
            'userID' => '2000',
            'orderId' => '20140423000001234',
            'amt' => '10',
            'succ' => 'Y',
            'hmac2' => 'x'
        ];

        $cldinpay->setOptions($sourceData);
        $cldinpay->verifyOrderPayment([]);
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

        $cldinpay = new Cldinpay();
        $cldinpay->setPrivateKey('1234');

        $sourceData = [
            'userID' => '2000',
            'orderId' => '20140423000001234',
            'amt' => '10',
            'succ' => 'N',
            'hmac2' => 'be2c59707972408f6e2b4a43f82766fa'
        ];

        $cldinpay->setOptions($sourceData);
        $cldinpay->verifyOrderPayment([]);
    }

    /**
     * 測試線上支付單號不正確的情況
     */
    public function testOrderIdError()
    {
        $this->setExpectedException('BB\DurianBundle\Exception\PaymentException', 'Order Id error', 180061);

        $cldinpay = new Cldinpay();
        $cldinpay->setPrivateKey('1234');

        $sourceData = [
            'userID' => '2000',
            'orderId' => '20140423000001234',
            'amt' => '10',
            'succ' => 'Y',
            'hmac2' => '177c78a865e6d0d93033c7967a83917e'
        ];

        $entry = ['id' => '19990720'];

        $cldinpay->setOptions($sourceData);
        $cldinpay->verifyOrderPayment($entry);
    }

    /**
     * 測試線上支付金額不正確的情況
     */
    public function testOrderAmountError()
    {
        $this->setExpectedException('BB\DurianBundle\Exception\PaymentException', 'Order Amount error', 180058);

        $cldinpay = new Cldinpay();
        $cldinpay->setPrivateKey('1234');

        $sourceData = [
            'userID' => '2000',
            'orderId' => '20140423000001234',
            'amt' => '10',
            'succ' => 'Y',
            'hmac2' => '177c78a865e6d0d93033c7967a83917e'
        ];

        $entry = [
            'id' => '20140423000001234',
            'amount' => '1'
        ];

        $cldinpay->setOptions($sourceData);
        $cldinpay->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testPaySuccess()
    {
        $cldinpay = new Cldinpay();
        $cldinpay->setPrivateKey('1234');

        $sourceData = [
            'userID' => '2000',
            'orderId' => '20140423000001234',
            'amt' => '10',
            'succ' => 'Y',
            'hmac2' => '177c78a865e6d0d93033c7967a83917e'
        ];

        $entry = [
            'id' => '20140423000001234',
            'amount' => '10'
        ];

        $cldinpay->setOptions($sourceData);
        $cldinpay->verifyOrderPayment($entry);

        $this->assertEquals('[success]', $cldinpay->getMsg());
    }
}
