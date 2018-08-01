<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\VPay;

class VPayTest extends DurianTestCase
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

        $vPay = new VPay();
        $vPay->getVerifyData();
    }

    /**
     * 測試加密基本參數設定未指定支付參數
     */
    public function testSetEncodeSourceNoNumber()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No pay parameter specified',
            180145
        );

        $vPay = new VPay();
        $vPay->setPrivateKey('1234');

        $arrSourceData = ['number' => ''];

        $vPay->setOptions($arrSourceData);
        $vPay->getVerifyData();
    }

    /**
     * 測試加密
     */
    public function testGetEncodeData()
    {
        $sourceData = [
            'number' => '88000002',
            'orderId' => '20110331201103312011033120110331',
            'amount' => '1234.56',
            'notify_url' => 'http://esball.org/app/member/pay_online2/pay_result.php',
            'domain' => '1',
            'username' => '张三',
            'merchantId' => '12345',
        ];

        $vPay = new VPay();
        $vPay->setPrivateKey('1234');
        $vPay->setOptions($sourceData);
        $encodeData = $vPay->getVerifyData();

        $notifyUrl = sprintf(
            '%s?pay_system=%s',
            $sourceData['notify_url'],
            $sourceData['merchantId']
        );

        $this->assertEquals('88000002', $encodeData['P_UserId']);
        $this->assertEquals('20110331201103312011033120110331', $encodeData['P_OrderId']);
        $this->assertEquals('1234.56', $encodeData['P_FaceValue']);
        $this->assertEquals($notifyUrl, $encodeData['P_Result_URL']);
        $this->assertEquals('', $encodeData['P_Notify_URL']);
        $this->assertEquals('8ad77cffdbf953516950f8721914afd8', $encodeData['P_PostKey']);
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

        $vPay = new VPay();
        $vPay->setPrivateKey('');

        $arrSourceData = [];

        $vPay->setOptions($arrSourceData);
        $vPay->verifyOrderPayment([]);
    }

    /**
     * 測試解密驗證時支付平台未指定返回參數
     */
    public function testVerifyWithNoReturnParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $vPay = new VPay();
        $vPay->setPrivateKey('1234');

        $arrSourceData = [
            'P_OrderId'   => '20110331201103312011033120110331',
            'P_CardId'    => '',
            'P_CardPass'  => '',
            'P_FaceValue' => '1234.56',
            'P_ChannelId' => 1,
            'P_ErrCode'   => '0',
            'P_PayMoney'  => '1234.56',
            'P_PostKey'   => '8ad77cffdbf953516950f8721914afd'
        ];

        $vPay->setOptions($arrSourceData);
        $vPay->verifyOrderPayment([]);
    }

    /**
     * 測試解密驗證時支付平台缺少回傳參數(測試少回傳P_PostKey:加密簽名)
     */
    public function testVerifyWithoutPostKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $vPay = new VPay();
        $vPay->setPrivateKey('1234');

        $arrSourceData = [
            'P_UserId'    => '88000002',
            'P_OrderId'   => '20110331201103312011033120110331',
            'P_CardId'    => '',
            'P_CardPass'  => '',
            'P_FaceValue' => '1234.56',
            'P_ChannelId' => 1,
            'P_ErrCode'   => '0',
            'P_PayMoney'  => '1234.56'
        ];

        $vPay->setOptions($arrSourceData);
        $vPay->verifyOrderPayment([]);
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

        $vPay = new VPay();
        $vPay->setPrivateKey('1234');

        $arrSourceData = [
            'P_UserId'    => '88000002',
            'P_OrderId'   => '20110331201103312011033120110331',
            'P_CardId'    => '',
            'P_CardPass'  => '',
            'P_FaceValue' => '1234.56',
            'P_ChannelId' => 1,
            'P_ErrCode'   => '0',
            'P_PayMoney'  => '1234.56',
            'P_PostKey'   => '8ad77cffdbf953516950f8721914afd'
        ];

        $vPay->setOptions($arrSourceData);
        $vPay->verifyOrderPayment([]);
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

        $vPay = new VPay();
        $vPay->setPrivateKey('1234');

        $arrSourceData = [
            'P_UserId'    => '88000002',
            'P_OrderId'   => '20110331201103312011033120110331',
            'P_CardId'    => '',
            'P_CardPass'  => '',
            'P_FaceValue' => '1234.56',
            'P_ChannelId' => 1,
            'P_ErrCode'   => '101',
            'P_PayMoney'  => '1234.56',
            'P_PostKey'   => '449446a82671dfecf7ecbe36f8fd4fb4'
        ];

        $vPay->setOptions($arrSourceData);
        $vPay->verifyOrderPayment([]);
    }

    /**
     * 測試線上支付單號不正確的情況
     */
    public function testOrderIdError()
    {
        $this->setExpectedException('BB\DurianBundle\Exception\PaymentException', 'Order Id error', 180061);

        $vPay = new VPay();
        $vPay->setPrivateKey('1234');

        $arrSourceData = [
            'P_UserId'    => '88000002',
            'P_OrderId'   => '20110331201103312011033120110331',
            'P_CardId'    => '',
            'P_CardPass'  => '',
            'P_FaceValue' => '1234.56',
            'P_ChannelId' => 1,
            'P_ErrCode'   => '0',
            'P_PayMoney'  => '1234.56',
            'P_PostKey'   => '8ad77cffdbf953516950f8721914afd8',
        ];

        $entry = ['id' => '20140113143143'];

        $vPay->setOptions($arrSourceData);
        $vPay->verifyOrderPayment($entry);
    }

    /**
     * 測試線上支付金額不正確的情況
     */
    public function testOrderAmountError()
    {
        $this->setExpectedException('BB\DurianBundle\Exception\PaymentException', 'Order Amount error', 180058);

        $vPay = new VPay();
        $vPay->setPrivateKey('1234');

        $arrSourceData = [
            'P_UserId'    => '88000002',
            'P_OrderId'   => '20110331201103312011033120110331',
            'P_CardId'    => '',
            'P_CardPass'  => '',
            'P_FaceValue' => '1234.56',
            'P_ChannelId' => 1,
            'P_ErrCode'   => '0',
            'P_PayMoney'  => '1234.56',
            'P_PostKey'   => '8ad77cffdbf953516950f8721914afd8',
        ];

        $entry = [
            'id' => '20110331201103312011033120110331',
            'amount' => '12345.67'
        ];

        $vPay->setOptions($arrSourceData);
        $vPay->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testPaySuccess()
    {
        $vPay = new VPay();
        $vPay->setPrivateKey('1234');

        $arrSourceData = [
            'P_UserId'    => '88000002',
            'P_OrderId'   => '20110331201103312011033120110331',
            'P_CardId'    => '',
            'P_CardPass'  => '',
            'P_FaceValue' => '1234.56',
            'P_ChannelId' => 1,
            'P_ErrCode'   => '0',
            'P_PayMoney'  => '1234.56',
            'P_PostKey'   => '8ad77cffdbf953516950f8721914afd8',
        ];

        $entry = [
            'id' => '20110331201103312011033120110331',
            'amount' => '1234.56'
        ];

        $vPay->setOptions($arrSourceData);
        $vPay->verifyOrderPayment($entry);

        $this->assertEquals('errCode=0', $vPay->getMsg());
    }
}
