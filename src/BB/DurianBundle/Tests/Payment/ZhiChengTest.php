<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\ZhiCheng;

class ZhiChengTest extends DurianTestCase
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

        $zhiCheng = new ZhiCheng();
        $zhiCheng->getVerifyData();
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

        $zhiCheng = new ZhiCheng();
        $zhiCheng->setPrivateKey('test');
        $zhiCheng->getVerifyData();
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

        $options = [
            'number' => '866376450013133',
            'orderId' => '201801120000003635',
            'orderCreateDate' => '2018-01-12 11:13:04',
            'amount' => '0.01',
            'notify_url' => 'http://pay.my/pay/return.php',
            'paymentVendorId' => '999',
            'username' => 'php1test',
        ];

        $zhiCheng = new ZhiCheng();
        $zhiCheng->setPrivateKey('test');
        $zhiCheng->setOptions($options);
        $zhiCheng->getVerifyData();
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

        $options = [
            'number' => '866376450013133',
            'orderId' => '201801120000003635',
            'orderCreateDate' => '2018-01-12 11:13:04',
            'amount' => '0.01',
            'notify_url' => 'http://pay.my/pay/return.php',
            'paymentVendorId' => '1',
            'username' => 'php1test',
            'merchant_extra' => [],
        ];

        $zhiCheng = new ZhiCheng();
        $zhiCheng->setPrivateKey('test');
        $zhiCheng->setOptions($options);
        $zhiCheng->getVerifyData();
    }

    /**
     * 測試支付銀行為手機
     */
    public function testPayWithPhone()
    {
        $options = [
            'number' => '866376450013133',
            'orderId' => '201801120000003635',
            'orderCreateDate' => '2018-01-12 11:13:04',
            'amount' => '0.01',
            'notify_url' => 'http://pay.my/pay/return.php',
            'paymentVendorId' => '1100',
            'username' => 'php1test',
            'merchant_extra' => ['platformID' => '866376450013133'],
        ];

        $zhiCheng = new ZhiCheng();
        $zhiCheng->setPrivateKey('test');
        $zhiCheng->setOptions($options);
        $requestData = $zhiCheng->getVerifyData();

        $this->assertEquals('WAP_PAY_B2C', $requestData['apiName']);
        $this->assertEquals('866376450013133', $requestData['merchNo']);
        $this->assertEquals('201801120000003635', $requestData['orderNo']);
        $this->assertEquals('20180112', $requestData['tradeDate']);
        $this->assertEquals('0.01', $requestData['amt']);
        $this->assertEquals('http://pay.my/pay/return.php', $requestData['merchUrl']);
        $this->assertEquals('866376450013133', $requestData['platformID']);
        $this->assertEquals('696db49a306761f5d1a1ff6c611e122f', $requestData['signMsg']);
        $this->assertEquals('1', $requestData['choosePayType']);
    }

    /**
     * 測試支付傳成功
     */
    public function testPay()
    {
        $options = [
            'number' => '866376450013133',
            'orderId' => '201801120000003635',
            'orderCreateDate' => '2018-01-12 11:13:04',
            'amount' => '0.01',
            'notify_url' => 'http://pay.my/pay/return.php',
            'paymentVendorId' => '1',
            'username' => 'php1test',
            'merchant_extra' => ['platformID' => '866376450013133'],
        ];

        $zhiCheng = new ZhiCheng();
        $zhiCheng->setPrivateKey('test');
        $zhiCheng->setOptions($options);
        $requestData = $zhiCheng->getVerifyData();

        $this->assertEquals('WEB_PAY_B2C', $requestData['apiName']);
        $this->assertEquals('866376450013133', $requestData['merchNo']);
        $this->assertEquals('201801120000003635', $requestData['orderNo']);
        $this->assertEquals('20180112', $requestData['tradeDate']);
        $this->assertEquals('0.01', $requestData['amt']);
        $this->assertEquals('http://pay.my/pay/return.php', $requestData['merchUrl']);
        $this->assertEquals('866376450013133', $requestData['platformID']);
        $this->assertEquals('4f0f7fd36184297d805e057d053af9ca', $requestData['signMsg']);
        $this->assertEquals('1', $requestData['choosePayType']);
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

        $zhiCheng = new ZhiCheng();
        $zhiCheng->verifyOrderPayment([]);
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

        $zhiCheng = new ZhiCheng();
        $zhiCheng->setPrivateKey('test');
        $zhiCheng->verifyOrderPayment([]);
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

        $options = [
            'apiName' => 'PAY_RESULT_NOTIFY',
            'notifyTime' => '20180112150523',
            'tradeAmt' => '0.01',
            'merchNo' => '866376450013133',
            'merchParam' => '',
            'orderNo' => '201801120000003635',
            'tradeDate' => '20180112',
            'accNo' => '12270871',
            'accDate' => '20180112',
            'orderStatus' => '1',
            'notifyType' => '1',
        ];

        $zhiCheng = new ZhiCheng();
        $zhiCheng->setPrivateKey('test');
        $zhiCheng->setOptions($options);
        $zhiCheng->verifyOrderPayment([]);
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

        $options = [
            'apiName' => 'PAY_RESULT_NOTIFY',
            'notifyTime' => '20180112150523',
            'tradeAmt' => '0.01',
            'merchNo' => '866376450013133',
            'merchParam' => '',
            'orderNo' => '201801120000003635',
            'tradeDate' => '20180112',
            'accNo' => '12270871',
            'accDate' => '20180112',
            'orderStatus' => '1',
            'signMsg' => 'E533FF70AFC789712E5DA2939B295513',
            'notifyType' => '1',
        ];

        $zhiCheng = new ZhiCheng();
        $zhiCheng->setPrivateKey('test');
        $zhiCheng->setOptions($options);
        $zhiCheng->verifyOrderPayment([]);
    }

    /**
     * 測試返回時訂單未支付
     */
    public function testReturnUnpaidOrder()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Unpaid order',
            180062
        );

        $options = [
            'apiName' => 'PAY_RESULT_NOTIFY',
            'notifyTime' => '20180112150523',
            'tradeAmt' => '0.01',
            'merchNo' => '866376450013133',
            'merchParam' => '',
            'orderNo' => '201801120000003635',
            'tradeDate' => '20180112',
            'accNo' => '12270871',
            'accDate' => '20180112',
            'orderStatus' => '0',
            'signMsg' => '6c7e43540bed18945c0b586f16bb92c3',
            'notifyType' => '1',
        ];

        $zhiCheng = new ZhiCheng();
        $zhiCheng->setPrivateKey('test');
        $zhiCheng->setOptions($options);
        $zhiCheng->verifyOrderPayment([]);
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

        $options = [
            'apiName' => 'PAY_RESULT_NOTIFY',
            'notifyTime' => '20180112150523',
            'tradeAmt' => '0.01',
            'merchNo' => '866376450013133',
            'merchParam' => '',
            'orderNo' => '201801120000003635',
            'tradeDate' => '20180112',
            'accNo' => '12270871',
            'accDate' => '20180112',
            'orderStatus' => '2',
            'signMsg' => 'd7db90676748b2b6279547fff3ae42b7',
            'notifyType' => '1',
        ];

        $zhiCheng = new ZhiCheng();
        $zhiCheng->setPrivateKey('test');
        $zhiCheng->setOptions($options);
        $zhiCheng->verifyOrderPayment([]);
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

        $options = [
            'apiName' => 'PAY_RESULT_NOTIFY',
            'notifyTime' => '20180112150523',
            'tradeAmt' => '0.01',
            'merchNo' => '866376450013133',
            'merchParam' => '',
            'orderNo' => '201801120000003635',
            'tradeDate' => '20180112',
            'accNo' => '12270871',
            'accDate' => '20180112',
            'orderStatus' => '1',
            'signMsg' => 'f98eebe6e762d69cda58b5fcba1f2655',
            'notifyType' => '1',
        ];

        $entry = [
            'id' => '201801120000003636',
            'amount' => '0.01',
        ];

        $zhiCheng = new ZhiCheng();
        $zhiCheng->setPrivateKey('test');
        $zhiCheng->setOptions($options);
        $zhiCheng->verifyOrderPayment($entry);
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

        $options = [
            'apiName' => 'PAY_RESULT_NOTIFY',
            'notifyTime' => '20180112150523',
            'tradeAmt' => '0.01',
            'merchNo' => '866376450013133',
            'merchParam' => '',
            'orderNo' => '201801120000003635',
            'tradeDate' => '20180112',
            'accNo' => '12270871',
            'accDate' => '20180112',
            'orderStatus' => '1',
            'signMsg' => 'f98eebe6e762d69cda58b5fcba1f2655',
            'notifyType' => '1',
        ];

        $entry = [
            'id' => '201801120000003635',
            'amount' => '1',
        ];

        $zhiCheng = new ZhiCheng();
        $zhiCheng->setPrivateKey('test');
        $zhiCheng->setOptions($options);
        $zhiCheng->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果
     */
    public function testReturnOrder()
    {
        $options = [
            'apiName' => 'PAY_RESULT_NOTIFY',
            'notifyTime' => '20180112150523',
            'tradeAmt' => '0.01',
            'merchNo' => '866376450013133',
            'merchParam' => '',
            'orderNo' => '201801120000003635',
            'tradeDate' => '20180112',
            'accNo' => '12270871',
            'accDate' => '20180112',
            'orderStatus' => '1',
            'signMsg' => 'f98eebe6e762d69cda58b5fcba1f2655',
            'notifyType' => '1',
        ];

        $entry = [
            'id' => '201801120000003635',
            'amount' => '0.01',
        ];

        $zhiCheng = new ZhiCheng();
        $zhiCheng->setPrivateKey('test');
        $zhiCheng->setOptions($options);
        $zhiCheng->verifyOrderPayment($entry);

        $this->assertEquals('SUCCESS', $zhiCheng->getMsg());
    }
}
