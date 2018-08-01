<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\WangFuTong;

class WangFuTongTest extends DurianTestCase
{
    /**
     * 測試支付時缺少私鑰
     */
    public function testPayWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $wangFuTong = new WangFuTong();
        $wangFuTong->getVerifyData();
    }

    /**
     * 測試支付時未指定支付參數
     */
    public function testPayWithNoPayParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No pay parameter specified',
            180145
        );

        $wangFuTong = new WangFuTong();
        $wangFuTong->setPrivateKey('test');
        $wangFuTong->getVerifyData();
    }

    /**
     * 測試支付設定帶入不支援的銀行
     */
    public function testPaySourceNotSupportBank()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'PaymentVendor is not supported by PaymentGateway',
            180066
        );

        $sourceData = [
            'number' => '9527',
            'orderId' => '20171124114612',
            'amount' => '100.00',
            'notify_url' => 'http://seafood.com/',
            'paymentVendorId' => '99',
            'username' => 'seafood',
        ];

        $wangFuTong = new WangFuTong();
        $wangFuTong->setPrivateKey('test');
        $wangFuTong->setOptions($sourceData);
        $wangFuTong->getVerifyData();
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $sourceData = [
            'number' => '9527',
            'orderId' => '20171124114612',
            'amount' => '100.00',
            'notify_url' => 'http://seafood.com/',
            'paymentVendorId' => '1',
            'username' => 'seafood',
        ];

        $wangFuTong = new WangFuTong();
        $wangFuTong->setPrivateKey('test');
        $wangFuTong->setOptions($sourceData);
        $requestData = $wangFuTong->getVerifyData();

        $this->assertEquals('9527', $requestData['MerId']);
        $this->assertEquals('20171124114612', $requestData['OrdId']);
        $this->assertEquals('100.00', $requestData['OrdAmt']);
        $this->assertEquals('DT', $requestData['PayType']);
        $this->assertEquals('RMB', $requestData['CurCode']);
        $this->assertEquals('ICBC', $requestData['BankCode']);
        $this->assertEquals('seafood', $requestData['ProductInfo']);
        $this->assertEquals('seafood', $requestData['Remark']);
        $this->assertEquals($sourceData['notify_url'], $requestData['ReturnURL']);
        $this->assertEquals($sourceData['notify_url'], $requestData['NotifyURL']);
        $this->assertEquals('MD5', $requestData['SignType']);
        $this->assertEquals('655468775a41bd2f46a127d947ce28b1', $requestData['SignInfo']);
    }

    /**
     * 測試返回時缺少私鑰
     */
    public function testReturnWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $wangFuTong = new WangFuTong();
        $wangFuTong->verifyOrderPayment([]);
    }

    /**
     * 測試返回時未指定返回參數
     */
    public function testReturnWithNoReturnParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $wangFuTong = new WangFuTong();
        $wangFuTong->setPrivateKey('test');
        $wangFuTong->verifyOrderPayment([]);
    }

    /**
     * 測試返回時缺少加密簽名
     */
    public function testReturnWithoutSignMsg()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $sourceData = [
            'MerId' => '9527',
            'OrdId' => '20171124114656',
            'OrdAmt' => '100.00',
            'OrdNo' => 'PADT2017112710181491819011',
            'ResultCode' => 'success002',
            'Remark' => 'seafood',
            'SignType' => 'MD5',
        ];

        $wangFuTong = new WangFuTong();
        $wangFuTong->setPrivateKey('test');
        $wangFuTong->setOptions($sourceData);
        $wangFuTong->verifyOrderPayment([]);
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

        $sourceData = [
            'MerId' => '9527',
            'OrdId' => '20171124114656',
            'OrdAmt' => '100.00',
            'OrdNo' => 'PADT2017112710181491819011',
            'ResultCode' => 'success002',
            'Remark' => 'seafood',
            'SignType' => 'MD5',
            'SignInfo' => 'SeafoodIsGood',
        ];

        $wangFuTong = new WangFuTong();
        $wangFuTong->setPrivateKey('test');
        $wangFuTong->setOptions($sourceData);
        $wangFuTong->verifyOrderPayment([]);
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

        $sourceData = [
            'MerId' => '9527',
            'OrdId' => '20171124114656',
            'OrdAmt' => '100.00',
            'OrdNo' => 'PADT2017112710181491819011',
            'ResultCode' => 'failed',
            'Remark' => 'seafood',
            'SignType' => 'MD5',
            'SignInfo' => '205bd1b3c307f4b71acb7bbb8d829b7a',
        ];

        $wangFuTong = new WangFuTong();
        $wangFuTong->setPrivateKey('test');
        $wangFuTong->setOptions($sourceData);
        $wangFuTong->verifyOrderPayment([]);
    }

    /**
     * 測試返回結果為訂單號錯誤
     */
    public function testReturnOrderIdError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $sourceData = [
            'MerId' => '9527',
            'OrdId' => '20171124114656',
            'OrdAmt' => '100.00',
            'OrdNo' => 'PADT2017112710181491819011',
            'ResultCode' => 'success002',
            'Remark' => 'seafood',
            'SignType' => 'MD5',
            'SignInfo' => '23676a649940cdf2b7ef8164ec5a2c41',
        ];

        $entry = ['id' => '201705220000000321'];

        $wangFuTong = new WangFuTong();
        $wangFuTong->setPrivateKey('test');
        $wangFuTong->setOptions($sourceData);
        $wangFuTong->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果為訂單金額錯誤
     */
    public function testReturnOrderAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $sourceData = [
            'MerId' => '9527',
            'OrdId' => '20171124114656',
            'OrdAmt' => '100.00',
            'OrdNo' => 'PADT2017112710181491819011',
            'ResultCode' => 'success002',
            'Remark' => 'seafood',
            'SignType' => 'MD5',
            'SignInfo' => '23676a649940cdf2b7ef8164ec5a2c41',
        ];

        $entry = [
            'id' => '20171124114656',
            'amount' => '10.00',
        ];

        $wangFuTong = new WangFuTong();
        $wangFuTong->setPrivateKey('test');
        $wangFuTong->setOptions($sourceData);
        $wangFuTong->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果
     */
    public function testReturnOrder()
    {
        $sourceData = [
            'MerId' => '9527',
            'OrdId' => '20171124114656',
            'OrdAmt' => '100.00',
            'OrdNo' => 'PADT2017112710181491819011',
            'ResultCode' => 'success002',
            'Remark' => 'seafood',
            'SignType' => 'MD5',
            'SignInfo' => '23676a649940cdf2b7ef8164ec5a2c41',
        ];

        $entry = [
            'id' => '20171124114656',
            'amount' => '100.00',
        ];

        $wangFuTong = new WangFuTong();
        $wangFuTong->setPrivateKey('test');
        $wangFuTong->setOptions($sourceData);
        $wangFuTong->verifyOrderPayment($entry);

        $this->assertEquals('success|9999', $wangFuTong->getMsg());
    }
}
