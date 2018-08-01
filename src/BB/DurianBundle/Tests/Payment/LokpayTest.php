<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\Lokpay;

class LokpayTest extends DurianTestCase
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

        $lokpay = new Lokpay();
        $lokpay->getVerifyData();
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

        $lokpay = new Lokpay();
        $lokpay->setPrivateKey('1234');

        $sourceData = ['number' => ''];

        $lokpay->setOptions($sourceData);
        $lokpay->getVerifyData();
    }

    /**
     * 測試加密
     */
    public function testGetEncodeData()
    {
        $sourceData = [
            'number' => '2000',
            'orderId' => '20140423000001234',
            'amount' => '10',
            'notify_url' => 'http://esball.org/app/member/pay_online2/pay_result.php',
            'username' => 'MyPay',
            'domain' => '1',
            'merchantId' => '1',
        ];

        $lokpay = new Lokpay();
        $lokpay->setPrivateKey('1234');
        $lokpay->setOptions($sourceData);
        $encodeData = $lokpay->getVerifyData();

        $this->assertEquals($sourceData['number'], $encodeData['P_UserId']);
        $this->assertEquals($sourceData['orderId'], $encodeData['P_OrderId']);
        $this->assertEquals($sourceData['amount'], $encodeData['P_FaceValue']);
        $this->assertEquals($sourceData['notify_url'], $encodeData['P_Result_URL']);
        $this->assertEquals('', $encodeData['P_Notify_URL']);
        $this->assertEquals($sourceData['username'], $encodeData['P_Subject']);
        $this->assertEquals($sourceData['domain'], $encodeData['P_Description']);
        $this->assertEquals($sourceData['merchantId'], $encodeData['P_Notic']);
        $this->assertEquals('b6169c1160e26ef3f7f0f37aa96f6293', $encodeData['P_PostKey']);
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

        $lokpay = new Lokpay();

        $lokpay->verifyOrderPayment([]);
    }

    /**
     * 測試解密驗證時沒有支付未指定返回參數
     */
    public function testVerifyWithNoReturnParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $lokpay = new Lokpay();
        $lokpay->setPrivateKey('1234');

        $sourceData = [
            'P_UserId'    => '2000',
            'P_OrderId'   => '20140423000001234',
            'P_CardId'    => 'S0989899809342343443',
            'P_CardPass'  => '908932849',
            'P_ChannelId' => '3',
            'P_ErrCode'   => '0',
            'P_PostKey'   => '17babb8fb2b7d395f7410068d110f3b0'
        ];

        $lokpay->setOptions($sourceData);
        $lokpay->verifyOrderPayment([]);
    }

    /**
     * 測試解密驗證時沒有支付平台缺少回傳參數(測試P_PostKey:加密簽名)
     */
    public function testVerifyWithoutPostKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $lokpay = new Lokpay();
        $lokpay->setPrivateKey('1234');

        $sourceData = [
            'P_UserId'    => '2000',
            'P_OrderId'   => '20140423000001234',
            'P_CardId'    => 'S0989899809342343443',
            'P_CardPass'  => '908932849',
            'P_FaceValue' => '10.00',
            'P_ChannelId' => '3',
            'P_ErrCode'   => '0'
        ];

        $lokpay->setOptions($sourceData);
        $lokpay->verifyOrderPayment([]);
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

        $lokpay = new Lokpay();
        $lokpay->setPrivateKey('1234');

        $sourceData = [
            'P_UserId'    => '2000',
            'P_OrderId'   => '20140423000001234',
            'P_CardId'    => 'S0989899809342343443',
            'P_CardPass'  => '908932849',
            'P_FaceValue' => '10.00',
            'P_ChannelId' => '3',
            'P_ErrCode'   => '0',
            'P_PostKey'   => 'x'
        ];

        $lokpay->setOptions($sourceData);
        $lokpay->verifyOrderPayment([]);
    }

    /**
     * 測試解密基本參數設定沒有帶入P_ErrCode的情況
     */
    public function testSetDecodeSourceNoErrCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $lokpay = new Lokpay();
        $lokpay->setPrivateKey('1234');

        $sourceData = [
            'P_UserId'    => '2000',
            'P_OrderId'   => '20140423000001234',
            'P_CardId'    => 'S0989899809342343443',
            'P_CardPass'  => '908932849',
            'P_FaceValue' => '10.00',
            'P_ChannelId' => '3',
            'P_PostKey'   => '17babb8fb2b7d395f7410068d110f3b0'
        ];

        $lokpay->setOptions($sourceData);
        $lokpay->verifyOrderPayment([]);
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

        $lokpay = new Lokpay();
        $lokpay->setPrivateKey('1234');

        $sourceData = [
            'P_UserId'    => '2000',
            'P_OrderId'   => '20140423000001234',
            'P_CardId'    => 'S0989899809342343443',
            'P_CardPass'  => '908932849',
            'P_FaceValue' => '10.00',
            'P_ChannelId' => '3',
            'P_ErrCode'   => '1',
            'P_PostKey'   => '17babb8fb2b7d395f7410068d110f3b0'
        ];

        $lokpay->setOptions($sourceData);
        $lokpay->verifyOrderPayment([]);
    }

    /**
     * 測試線上支付單號不正確的情況
     */
    public function testOrderIdError()
    {
        $this->setExpectedException('BB\DurianBundle\Exception\PaymentException', 'Order Id error', 180061);

        $lokpay = new Lokpay();
        $lokpay->setPrivateKey('1234');

        $sourceData = [
            'P_UserId'    => '2000',
            'P_OrderId'   => '20140423000001234',
            'P_CardId'    => 'S0989899809342343443',
            'P_CardPass'  => '908932849',
            'P_FaceValue' => '10.00',
            'P_ChannelId' => '3',
            'P_ErrCode'   => '0',
            'P_PostKey'   => '17babb8fb2b7d395f7410068d110f3b0'
        ];

        $entry = ['id' => '19990720'];

        $lokpay->setOptions($sourceData);
        $lokpay->verifyOrderPayment($entry);
    }

    /**
     * 測試線上支付金額不正確的情況
     */
    public function testOrderAmountError()
    {
        $this->setExpectedException('BB\DurianBundle\Exception\PaymentException', 'Order Amount error', 180058);

        $lokpay = new Lokpay();
        $lokpay->setPrivateKey('1234');

        $sourceData = [
            'P_UserId'    => '2000',
            'P_OrderId'   => '20140423000001234',
            'P_CardId'    => 'S0989899809342343443',
            'P_CardPass'  => '908932849',
            'P_FaceValue' => '10.00',
            'P_ChannelId' => '3',
            'P_ErrCode'   => '0',
            'P_PostKey'   => '17babb8fb2b7d395f7410068d110f3b0'
        ];

        $entry = [
            'id' => '20140423000001234',
            'amount' => '1.00'
        ];

        $lokpay->setOptions($sourceData);
        $lokpay->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testPaySuccess()
    {
        $lokpay = new Lokpay();
        $lokpay->setPrivateKey('1234');

        $sourceData = [
            'P_UserId'    => '2000',
            'P_OrderId'   => '20140423000001234',
            'P_CardId'    => 'S0989899809342343443',
            'P_CardPass'  => '908932849',
            'P_FaceValue' => '10.00',
            'P_ChannelId' => '3',
            'P_ErrCode'   => '0',
            'P_PostKey'   => '17babb8fb2b7d395f7410068d110f3b0'
        ];

        $entry = [
            'id' => '20140423000001234',
            'amount' => '10.00'
        ];

        $lokpay->setOptions($sourceData);
        $lokpay->verifyOrderPayment($entry);

        $this->assertEquals('errCode=0', $lokpay->getMsg());
    }
}
