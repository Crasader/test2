<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\YeetPay;

class YeetPayTest extends DurianTestCase
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

        $yeetPay = new YeetPay();
        $yeetPay->getVerifyData();
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

        $yeetPay = new YeetPay();
        $yeetPay->setPrivateKey('HpcIrKXaAA6ra');

        $sourceData = ['number' => ''];

        $yeetPay->setOptions($sourceData);
        $yeetPay->getVerifyData();
    }

    /**
     * 測試加密基本參數設定沒有帶入posturl的情況
     */
    public function testSetEncodeSourceNoPostUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No pay parameter specified',
            180145
        );

        $yeetPay = new YeetPay();
        $yeetPay->setPrivateKey('HpcIrKXaAA6ra');

        $sourceData = [
            'number' => '16875',
            'orderId' => '2007080110833',
            'amount' => '100',
            'username' => 'test',
            'notify_url' => 'http://www.yeetpay.com/pay/gateway.asp',
            'domain' => '6',
            'merchantId' => '12345',
        ];

        $yeetPay->setOptions($sourceData);
        $yeetPay->getVerifyData();
    }

    /**
     * 測試加密
     */
    public function testGetEncodeData()
    {
        $sourceData = [
            'number' => '16875',
            'orderId' => '2007080110833',
            'amount' => '100',
            'username' => 'test',
            'notify_url' => 'http://esball.org/app/member/pay_online2/pay_result.php',
            'domain' => '6',
            'postUrl' => 'http://www.yeetpay.com/pay/gateway.asp',
            'merchantId' => '12345',
        ];

        $yeetPay = new YeetPay();
        $yeetPay->setPrivateKey('HpcIrKXaAA6ra');
        $yeetPay->setOptions($sourceData);
        $encodeData = $yeetPay->getVerifyData();

        $notifyUrl = sprintf(
            '%s?pay_system=%s&hallid=%s',
            $sourceData['notify_url'],
            $sourceData['merchantId'],
            $sourceData['domain']
        );

        $url = '%s?P_UserId=%s&P_OrderId=%s&P_CardId=&P_CardPass=&P_FaceValue=%s&'.
            'P_ChannelId=1&P_Subject=test&P_Price=%s&P_Quantity=1&P_Description=&'.
            'P_Notic=%s&P_Result_URL=%s&P_Notify_URL=&'.
            'P_PostKey=51da812fa3f4e97ec71c0d4dad66e923';

        $url = sprintf(
            $url,
            $sourceData['postUrl'],
            $sourceData['number'],
            $sourceData['orderId'],
            sprintf("%.2f", $sourceData['amount']),
            sprintf("%.2f", $sourceData['amount']),
            $sourceData['domain'],
            $notifyUrl
        );

        $this->assertEquals($sourceData['number'], $encodeData['P_UserId']);
        $this->assertEquals($sourceData['orderId'], $encodeData['P_OrderId']);
        $this->assertEquals($sourceData['amount'], $encodeData['P_FaceValue']);
        $this->assertEquals($sourceData['amount'], $encodeData['P_Price']);
        $this->assertEquals($sourceData['username'], $encodeData['P_Subject']);
        $this->assertEquals($notifyUrl, $encodeData['P_Result_URL']);
        $this->assertEquals('', $encodeData['P_Notify_URL']);
        $this->assertEquals($sourceData['domain'], $encodeData['P_Notic']);
        $this->assertEquals(1, $encodeData['P_ChannelId']);
        $this->assertEquals('51da812fa3f4e97ec71c0d4dad66e923', $encodeData['P_PostKey']);
        $this->assertEquals($url, $encodeData['act_url']);
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

        $yeetPay = new YeetPay();

        $yeetPay->verifyOrderPayment([]);
    }

    /**
     * 測試解密驗證缺少postkey
     */
    public function testVerifyWithoutPostKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $yeetPay = new YeetPay();
        $yeetPay->setPrivateKey('HpcIrKXaAA6ra');

        $sourceData = [
            'P_UserId'    => '16875',
            'P_OrderId'   => '2007080110833',
            'P_CardId'    => '',
            'P_CardPass'  => '',
            'P_FaceValue' => '100',
            'P_ChannelId' => '1',
            'P_ErrCode'   => '0'
        ];

        $yeetPay->setOptions($sourceData);
        $yeetPay->verifyOrderPayment([]);
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

        $yeetPay = new YeetPay();
        $yeetPay->setPrivateKey('HpcIrKXaAA6ra');

        $sourceData = [
            'P_UserId'    => '16875',
            'P_OrderId'   => '2007080110833',
            'P_CardId'    => '',
            'P_CardPass'  => '',
            'P_FaceValue' => '100',
            'P_ChannelId' => '1',
            'P_ErrCode'   => '0',
            'P_PostKey'   => 'a6da43fbbee7bbef4a69301ba5eb200'
        ];

        $yeetPay->setOptions($sourceData);
        $yeetPay->verifyOrderPayment([]);
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

        $yeetPay = new YeetPay();
        $yeetPay->setPrivateKey('HpcIrKXaAA6ra');

        $sourceData = [
            'P_UserId'    => '16875',
            'P_OrderId'   => '2007080110833',
            'P_CardId'    => '',
            'P_CardPass'  => '',
            'P_FaceValue' => '100',
            'P_ChannelId' => '1',
            'P_ErrCode'   => '101',
            'P_PostKey'   => 'a6da43fbbee7bbef4a69301ba5eb2005'
        ];

        $yeetPay->setOptions($sourceData);
        $yeetPay->verifyOrderPayment([]);
    }

    /**
     * 測試線上支付單號不正確的情況
     */
    public function testOrderIdError()
    {
        $this->setExpectedException('BB\DurianBundle\Exception\PaymentException', 'Order Id error', 180061);

        $yeetPay = new YeetPay();
        $yeetPay->setPrivateKey('HpcIrKXaAA6ra');

        $sourceData = [
            'P_UserId'    => '16875',
            'P_OrderId'   => '2007080110833',
            'P_CardId'    => '',
            'P_CardPass'  => '',
            'P_FaceValue' => '100',
            'P_ChannelId' => '1',
            'P_ErrCode'   => '0',
            'P_PostKey'   => 'a6da43fbbee7bbef4a69301ba5eb2005'
        ];

        $entry = ['id' => '200708011083'];

        $yeetPay->setOptions($sourceData);
        $yeetPay->verifyOrderPayment($entry);
    }

    /**
     * 測試線上支付金額不正確的情況
     */
    public function testOrderAmountError()
    {
        $this->setExpectedException('BB\DurianBundle\Exception\PaymentException', 'Order Amount error', 180058);

        $yeetPay = new YeetPay();
        $yeetPay->setPrivateKey('HpcIrKXaAA6ra');

        $sourceData = [
            'P_UserId'    => '16875',
            'P_OrderId'   => '2007080110833',
            'P_CardId'    => '',
            'P_CardPass'  => '',
            'P_FaceValue' => '100',
            'P_ChannelId' => '1',
            'P_ErrCode'   => '0',
            'P_PostKey'   => 'a6da43fbbee7bbef4a69301ba5eb2005'
        ];

        $entry = [
            'id' => '2007080110833',
            'amount' => '90'
        ];

        $yeetPay->setOptions($sourceData);
        $yeetPay->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證時未指定返回參數
     */
    public function testPayWithNoReturnParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $yeetPay = new YeetPay();
        $yeetPay->setPrivateKey('HpcIrKXaAA6ra');

        $sourceData = [
            'P_OrderId'   => '2007080110833',
            'P_CardId'    => '',
            'P_CardPass'  => '',
            'P_FaceValue' => '100',
            'P_ChannelId' => '1',
            'P_ErrCode'   => '0',
            'P_PostKey'   => 'a6da43fbbee7bbef4a69301ba5eb2005'
        ];

        $yeetPay->setOptions($sourceData);
        $yeetPay->verifyOrderPayment([]);
    }

    /**
     * 測試支付驗證成功
     */
    public function testPaySuccess()
    {
        $yeetPay = new YeetPay();
        $yeetPay->setPrivateKey('HpcIrKXaAA6ra');

        $sourceData = [
            'P_UserId'    => '16875',
            'P_OrderId'   => '2007080110833',
            'P_CardId'    => '',
            'P_CardPass'  => '',
            'P_FaceValue' => '100',
            'P_ChannelId' => '1',
            'P_ErrCode'   => '0',
            'P_PostKey'   => 'a6da43fbbee7bbef4a69301ba5eb2005'
        ];

        $entry = [
            'id' => '2007080110833',
            'amount' => '100'
        ];

        $yeetPay->setOptions($sourceData);
        $yeetPay->verifyOrderPayment($entry);

        $this->assertEquals('errCode=0', $yeetPay->getMsg());
    }
}
