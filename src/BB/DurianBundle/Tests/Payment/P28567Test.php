<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\P28567;

class P28567Test extends DurianTestCase
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

        $p28567 = new P28567();
        $p28567->getVerifyData();
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

        $p28567 = new P28567();
        $p28567->setPrivateKey('HpcIrKXaAA6raMMt');

        $sourceData = ['number' => ''];

        $p28567->setOptions($sourceData);
        $p28567->getVerifyData();
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
            'postUrl' => 'http://www.28567.com/GateWay/pay.asp',
            'merchantId' => '12345',
        ];

        $p28567 = new P28567();
        $p28567->setPrivateKey('HpcIrKXaAA6raMMt');
        $p28567->setOptions($sourceData);
        $encodeData = $p28567->getVerifyData();

        $notifyUrl = sprintf(
            '%s?pay_system=%s&hallid=%s',
            $sourceData['notify_url'],
            $sourceData['merchantId'],
            $sourceData['domain']
        );

        $url = '%s?P_UserId=%s&P_OrderId=%s&P_CardId=&P_CardPass=&P_FaceValue=%s&'.
            'P_ChannelId=1&P_Subject=%s&P_Price=%s&P_Quantity=1&P_Description=&'.
            'P_Notic=%s&P_Result_URL=%s&P_Notify_URL=%s&'.
            'P_PostKey=2e850cf7371cd2167ce19cdc7351a92d';

        $actUrl = sprintf(
            $url,
            $sourceData['postUrl'],
            $sourceData['number'],
            $sourceData['orderId'],
            sprintf('%.2f', $sourceData['amount']),
            $sourceData['username'],
            sprintf('%.2f', $sourceData['amount']),
            $sourceData['domain'],
            $notifyUrl,
            $notifyUrl
        );

        $this->assertEquals($sourceData['number'], $encodeData['P_UserId']);
        $this->assertEquals($sourceData['orderId'], $encodeData['P_OrderId']);
        $this->assertEquals($sourceData['amount'], $encodeData['P_FaceValue']);
        $this->assertEquals($sourceData['amount'], $encodeData['P_Price']);
        $this->assertEquals($sourceData['username'], $encodeData['P_Subject']);
        $this->assertEquals($notifyUrl, $encodeData['P_Result_URL']);
        $this->assertEquals($notifyUrl, $encodeData['P_Notify_URL']);
        $this->assertEquals($sourceData['domain'], $encodeData['P_Notic']);
        $this->assertEquals(1, $encodeData['P_ChannelId']);
        $this->assertEquals('2e850cf7371cd2167ce19cdc7351a92d', $encodeData['P_PostKey']);
        $this->assertEquals($actUrl, $encodeData['act_url']);
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

        $p28567 = new P28567();

        $p28567->verifyOrderPayment([]);
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

        $p28567 = new P28567();
        $p28567->setPrivateKey('HpcIrKXaAA6raMMt');

        $sourceData = [
            'P_OrderId' => '2007080110833',
            'P_CardId' => '',
            'P_CardPass' => '',
            'P_FaceValue' => 100,
            'P_ChannelId' => 1,
            'P_ErrCode' => 0
        ];

        $p28567->setOptions($sourceData);
        $p28567->verifyOrderPayment([]);
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

        $p28567 = new P28567();
        $p28567->setPrivateKey('HpcIrKXaAA6raMMt');

        $sourceData = [
            'P_UserId'    => '16875',
            'P_OrderId'   => '2007080110833',
            'P_CardId'    => '',
            'P_CardPass'  => '',
            'P_FaceValue' => 100,
            'P_ChannelId' => 1,
            'P_ErrCode'   => 0
        ];

        $p28567->setOptions($sourceData);
        $p28567->verifyOrderPayment([]);
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

        $p28567 = new P28567();
        $p28567->setPrivateKey('HpcIrKXaAA6raMMt');

        $sourceData = [
            'P_UserId'    => '16875',
            'P_OrderId'   => '2007080110833',
            'P_CardId'    => '',
            'P_CardPass'  => '',
            'P_FaceValue' => 100,
            'P_ChannelId' => 1,
            'P_ErrCode'   => 0,
            'P_PostKey'   => '8ac535b60c3d1e9fa5232ef006e37ff'
        ];

        $p28567->setOptions($sourceData);
        $p28567->verifyOrderPayment([]);
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

        $p28567 = new P28567();
        $p28567->setPrivateKey('HpcIrKXaAA6raMMt');

        $sourceData = [
            'P_UserId'    => '16875',
            'P_OrderId'   => '2007080110833',
            'P_CardId'    => '',
            'P_CardPass'  => '',
            'P_FaceValue' => 100,
            'P_ChannelId' => 1,
            'P_ErrCode'   => 1,
            'P_PostKey'   => '8ac535b60c3d1e9fa5232ef006e37ff1'
        ];

        $p28567->setOptions($sourceData);
        $p28567->verifyOrderPayment([]);
    }

    /**
     * 測試線上支付單號不正確的情況
     */
    public function testOrderIdError()
    {
        $this->setExpectedException('BB\DurianBundle\Exception\PaymentException', 'Order Id error', 180061);

        $p28567 = new P28567();
        $p28567->setPrivateKey('HpcIrKXaAA6raMMt');

        $sourceData = [
            'P_UserId'    => '16875',
            'P_OrderId'   => '2007080110833',
            'P_CardId'    => '',
            'P_CardPass'  => '',
            'P_FaceValue' => 100,
            'P_ChannelId' => 1,
            'P_ErrCode'   => 0,
            'P_PostKey'   => '8ac535b60c3d1e9fa5232ef006e37ff1'
        ];

        $entry = ['id' => '200708011083'];

        $p28567->setOptions($sourceData);
        $p28567->verifyOrderPayment($entry);
    }

    /**
     * 測試線上支付金額不正確的情況
     */
    public function testOrderAmountError()
    {
        $this->setExpectedException('BB\DurianBundle\Exception\PaymentException', 'Order Amount error', 180058);

        $p28567 = new P28567();
        $p28567->setPrivateKey('HpcIrKXaAA6raMMt');

        $sourceData = [
            'P_UserId'    => '16875',
            'P_OrderId'   => '2007080110833',
            'P_CardId'    => '',
            'P_CardPass'  => '',
            'P_FaceValue' => 100,
            'P_ChannelId' => 1,
            'P_ErrCode'   => 0,
            'P_PostKey'   => '8ac535b60c3d1e9fa5232ef006e37ff1'
        ];

        $entry = [
            'id' => '2007080110833',
            'amount' => '90'
        ];

        $p28567->setOptions($sourceData);
        $p28567->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testPaySuccess()
    {
        $p28567 = new P28567();
        $p28567->setPrivateKey('HpcIrKXaAA6raMMt');

        $sourceData = [
            'P_UserId'    => '16875',
            'P_OrderId'   => '2007080110833',
            'P_CardId'    => '',
            'P_CardPass'  => '',
            'P_FaceValue' => 100,
            'P_ChannelId' => 1,
            'P_ErrCode'   => 0,
            'P_PostKey'   => '8ac535b60c3d1e9fa5232ef006e37ff1'
        ];

        $entry = [
            'id' => '2007080110833',
            'amount' => '100'
        ];

        $p28567->setOptions($sourceData);
        $p28567->verifyOrderPayment($entry);

        $this->assertEquals('errCode=0', $p28567->getMsg());
    }
}
