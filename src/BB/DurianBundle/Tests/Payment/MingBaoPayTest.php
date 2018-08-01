<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Payment\MingBaoPay;
use BB\DurianBundle\Tests\DurianTestCase;

class MingBaoPayTest extends DurianTestCase
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

        $mingBaoPay = new MingBaoPay();
        $mingBaoPay->getVerifyData();
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

        $mingBaoPay = new MingBaoPay();
        $mingBaoPay->setPrivateKey('test');
        $mingBaoPay->getVerifyData();
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
            'orderId' => '201708240000009527',
            'amount' => '100',
            'notify_url' => 'http://154.58.78.54/',
            'paymentVendorId' => '99',
            'orderCreateDate' => '2017-08-24 21:25:29',
        ];

        $mingBaoPay = new MingBaoPay();
        $mingBaoPay->setPrivateKey('test');
        $mingBaoPay->setOptions($sourceData);
        $mingBaoPay->getVerifyData();
    }

    /**
     * 測試支付缺少商家額外的參數設定platformID
     */
    public function testPayWithoutMerchantExtraPlatformID()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No merchant extra value specified',
            180143
        );

        $sourceData = [
            'number' => '9527',
            'orderId' => '201708240000009527',
            'amount' => '100',
            'notify_url' => 'http://154.58.78.54/',
            'paymentVendorId' => '1090',
            'orderCreateDate' => '2017-08-24 21:25:29',
            'merchant_extra' => [],
        ];

        $mingBaoPay = new MingBaoPay();
        $mingBaoPay->setPrivateKey('test');
        $mingBaoPay->setOptions($sourceData);
        $mingBaoPay->getVerifyData();
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $sourceData = [
            'number' => '9527',
            'orderId' => '201708240000009527',
            'amount' => '100.00',
            'notify_url' => 'http://154.58.78.54/',
            'paymentVendorId' => '1090',
            'orderCreateDate' => '2017-08-24 21:25:29',
            'merchant_extra' => ['platformID' => '9527'],
            'username' => 'php1test',
        ];

        $mingBaoPay = new MingBaoPay();
        $mingBaoPay->setPrivateKey('test');
        $mingBaoPay->setOptions($sourceData);
        $requestData = $mingBaoPay->getVerifyData();

        $this->assertEquals('WEB_PAY_B2C', $requestData['apiName']);
        $this->assertEquals('9527', $requestData['merchNo']);
        $this->assertEquals('9527', $requestData['platformID']);
        $this->assertEquals('201708240000009527', $requestData['orderNo']);
        $this->assertEquals('20170824', $requestData['tradeDate']);
        $this->assertEquals('100.00', $requestData['amt']);
        $this->assertEquals('http://154.58.78.54/', $requestData['merchUrl']);
        $this->assertEquals('', $requestData['merchParam']);
        $this->assertEquals('', $requestData['tradeSummary']);
        $this->assertEquals('fdda054fe4287ef0871d1cb129209b2c', $requestData['signMsg']);
        $this->assertEquals('WEIXIN', $requestData['bankCode']);
        $this->assertEquals('', $requestData['choosePayType']);
    }

    /**
     * 測試支付銀行為微信WAP
     */
    public function testPayWithWeiXinWap()
    {
        $sourceData = [
            'number' => '9527',
            'orderId' => '201708240000009527',
            'amount' => '100.00',
            'notify_url' => 'http://154.58.78.54/',
            'paymentVendorId' => '1097',
            'orderCreateDate' => '2017-08-24 21:25:29',
            'merchant_extra' => ['platformID' => '9527'],
            'username' => 'php1test',
        ];

        $mingBaoPay = new MingBaoPay();
        $mingBaoPay->setPrivateKey('test');
        $mingBaoPay->setOptions($sourceData);
        $requestData = $mingBaoPay->getVerifyData();

        $this->assertEquals('WAP_PAY_B2C', $requestData['apiName']);
        $this->assertEquals('9527', $requestData['merchNo']);
        $this->assertEquals('9527', $requestData['platformID']);
        $this->assertEquals('201708240000009527', $requestData['orderNo']);
        $this->assertEquals('20170824', $requestData['tradeDate']);
        $this->assertEquals('100.00', $requestData['amt']);
        $this->assertEquals('http://154.58.78.54/', $requestData['merchUrl']);
        $this->assertEquals('', $requestData['merchParam']);
        $this->assertEquals('', $requestData['tradeSummary']);
        $this->assertEquals('3936cb86b04137c1c058542b45f42ec2', $requestData['signMsg']);
        $this->assertEquals('WXWAP', $requestData['bankCode']);
        $this->assertEquals('', $requestData['choosePayType']);
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

        $mingBaoPay = new MingBaoPay();
        $mingBaoPay->verifyOrderPayment([]);
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

        $mingBaoPay = new MingBaoPay();
        $mingBaoPay->setPrivateKey('test');
        $mingBaoPay->verifyOrderPayment([]);
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
            'apiName' => 'PAY_RESULT_NOTIFY',
            'notifyTime' => '20170824211438',
            'tradeAmt' => '100.00',
            'merchNo' => 'acctest',
            'merchParam' => '',
            'orderNo' => '201708240000009527',
            'tradeDate' => '20170824',
            'accNo' => '722216',
            'accDate' => '20150316',
            'orderStatus' => '1',
        ];

        $mingBaoPay = new MingBaoPay();
        $mingBaoPay->setPrivateKey('test');
        $mingBaoPay->setOptions($sourceData);
        $mingBaoPay->verifyOrderPayment([]);
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
            'apiName' => 'PAY_RESULT_NOTIFY',
            'notifyTime' => '20170824211438',
            'tradeAmt' => '100.00',
            'merchNo' => 'acctest',
            'merchParam' => '',
            'orderNo' => '201708240000009527',
            'tradeDate' => '20170824',
            'accNo' => '722216',
            'accDate' => '20150316',
            'orderStatus' => '1',
            'signMsg' => 'poweiissohansome',
        ];

        $mingBaoPay = new MingBaoPay();
        $mingBaoPay->setPrivateKey('test');
        $mingBaoPay->setOptions($sourceData);
        $mingBaoPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時支付失敗
     */
    public function testReturnUnpaidOrder()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Unpaid order',
            180062
        );

        $sourceData = [
            'apiName' => 'PAY_RESULT_NOTIFY',
            'notifyTime' => '20170824211438',
            'tradeAmt' => '100.00',
            'merchNo' => 'acctest',
            'merchParam' => '',
            'orderNo' => '201708240000009527',
            'tradeDate' => '20170824',
            'accNo' => '722216',
            'accDate' => '20170824',
            'orderStatus' => '0',
            'signMsg' => '81b902ec97a12394829605e45460d8a7',
        ];

        $mingBaoPay = new MingBaoPay();
        $mingBaoPay->setPrivateKey('test');
        $mingBaoPay->setOptions($sourceData);
        $mingBaoPay->verifyOrderPayment([]);
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
            'apiName' => 'PAY_RESULT_NOTIFY',
            'notifyTime' => '20170824211438',
            'tradeAmt' => '100.00',
            'merchNo' => 'acctest',
            'merchParam' => '',
            'orderNo' => '201708240000009527',
            'tradeDate' => '20170824',
            'accNo' => '722216',
            'accDate' => '20170824',
            'orderStatus' => '99',
            'signMsg' => 'a6aec8b94bcb048c4f037d603bd13e52',
        ];

        $mingBaoPay = new MingBaoPay();
        $mingBaoPay->setPrivateKey('test');
        $mingBaoPay->setOptions($sourceData);
        $mingBaoPay->verifyOrderPayment([]);
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
            'apiName' => 'PAY_RESULT_NOTIFY',
            'notifyTime' => '20170824211438',
            'tradeAmt' => '100.00',
            'merchNo' => 'acctest',
            'merchParam' => '',
            'orderNo' => '201708240000009527',
            'tradeDate' => '20170824',
            'accNo' => '722216',
            'accDate' => '20170824',
            'orderStatus' => '1',
            'signMsg' => 'ffc501bc0acbbc64b7118fed58af70c7',
        ];

        $entry = ['id' => '201708240000000321'];

        $mingBaoPay = new MingBaoPay();
        $mingBaoPay->setPrivateKey('test');
        $mingBaoPay->setOptions($sourceData);
        $mingBaoPay->verifyOrderPayment($entry);
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
            'apiName' => 'PAY_RESULT_NOTIFY',
            'notifyTime' => '20170824211438',
            'tradeAmt' => '100.00',
            'merchNo' => 'acctest',
            'merchParam' => '',
            'orderNo' => '201708240000009527',
            'tradeDate' => '20170824',
            'accNo' => '722216',
            'accDate' => '20170824',
            'orderStatus' => '1',
            'signMsg' => 'ffc501bc0acbbc64b7118fed58af70c7',
        ];

        $entry = [
            'id' => '201708240000009527',
            'amount' => '10.00',
        ];

        $mingBaoPay = new MingBaoPay();
        $mingBaoPay->setPrivateKey('test');
        $mingBaoPay->setOptions($sourceData);
        $mingBaoPay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果
     */
    public function testReturnOrder()
    {
        $sourceData = [
            'apiName' => 'PAY_RESULT_NOTIFY',
            'notifyTime' => '20170824211438',
            'tradeAmt' => '100.00',
            'merchNo' => 'acctest',
            'merchParam' => '',
            'orderNo' => '201708240000009527',
            'tradeDate' => '20170824',
            'accNo' => '722216',
            'accDate' => '20170824',
            'orderStatus' => '1',
            'signMsg' => 'ffc501bc0acbbc64b7118fed58af70c7',
        ];

        $entry = [
            'id' => '201708240000009527',
            'amount' => '100.00',
        ];

        $mingBaoPay = new MingBaoPay();
        $mingBaoPay->setPrivateKey('test');
        $mingBaoPay->setOptions($sourceData);
        $mingBaoPay->verifyOrderPayment($entry);

        $this->assertEquals('SUCCESS', $mingBaoPay->getMsg());
    }
}
