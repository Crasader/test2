<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\ChengXinYunPay;

class ChengXinYunPayTest extends DurianTestCase
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

        $chengXinYunPay = new ChengXinYunPay();
        $chengXinYunPay->getVerifyData();
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

        $chengXinYunPay = new ChengXinYunPay();
        $chengXinYunPay->setPrivateKey('test');
        $chengXinYunPay->getVerifyData();
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
            'number' => '2105411515393243',
            'orderId' => '201803010000009807',
            'orderCreateDate' => '2018-03-05 11:13:04',
            'amount' => '1',
            'notify_url' => 'http://pay.simu/',
            'paymentVendorId' => '9999',
            'username' => 'php1test',
        ];

        $chengXinYunPay = new ChengXinYunPay();
        $chengXinYunPay->setPrivateKey('test');
        $chengXinYunPay->setOptions($options);
        $chengXinYunPay->getVerifyData();
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
            'number' => '2105411515393243',
            'orderId' => '201803010000009807',
            'orderCreateDate' => '2018-03-05 11:13:04',
            'amount' => '1',
            'notify_url' => 'http://pay.simu/',
            'paymentVendorId' => '1',
            'merchant_extra' => [],
            'username' => 'php1test',
        ];

        $chengXinYunPay = new ChengXinYunPay();
        $chengXinYunPay->setPrivateKey('test');
        $chengXinYunPay->setOptions($options);
        $chengXinYunPay->getVerifyData();
    }

    /**
     * 測試支付設定回傳成功
     */
    public function testPay()
    {
        $options = [
            'number' => '2105411511437320',
            'orderId' => '201712080000007383',
            'orderCreateDate' => '2017-12-08 11:13:04',
            'amount' => '100.00',
            'notify_url' => 'http://pay.simu/pay/return.php',
            'paymentVendorId' => '1',
            'merchant_extra' => ['platformID' => '2105411511437320'],
            'username' => 'php1test',
        ];

        $chengXinYunPay = new ChengXinYunPay();
        $chengXinYunPay->setPrivateKey('test');
        $chengXinYunPay->setOptions($options);
        $requestData = $chengXinYunPay->getVerifyData();

        $this->assertEquals('WEB_PAY_B2C', $requestData['apiName']);
        $this->assertEquals($options['number'], $requestData['merchNo']);
        $this->assertEquals($options['orderId'], $requestData['orderNo']);
        $this->assertEquals('20171208', $requestData['tradeDate']);
        $this->assertEquals($options['amount'], $requestData['amt']);
        $this->assertEquals($options['notify_url'], $requestData['merchUrl']);
        $this->assertEquals($options['merchant_extra']['platformID'], $requestData['platformID']);
        $this->assertEquals('39f534fc765c7c24edd4e143b123f28c', $requestData['signMsg']);
        $this->assertEquals('J_ICBC', $requestData['bankCode']);
        $this->assertEquals('1', $requestData['choosePayType']);
    }

    /**
     * 測試支付銀行為二維
     */
    public function testPayWithScan()
    {
        $options = [
            'number' => '2105411511437320',
            'orderId' => '201712080000007383',
            'orderCreateDate' => '2017-12-08 11:13:04',
            'amount' => '100.00',
            'notify_url' => 'http://pay.simu/pay/return.php',
            'paymentVendorId' => '1090',
            'merchant_extra' => ['platformID' => '2105411511437320'],
            'username' => 'php1test',
        ];

        $chengXinYunPay = new ChengXinYunPay();
        $chengXinYunPay->setPrivateKey('test');
        $chengXinYunPay->setOptions($options);
        $requestData = $chengXinYunPay->getVerifyData();

        $this->assertEquals('WEB_PAY_B2C', $requestData['apiName']);
        $this->assertEquals($options['number'], $requestData['merchNo']);
        $this->assertEquals($options['orderId'], $requestData['orderNo']);
        $this->assertEquals('20171208', $requestData['tradeDate']);
        $this->assertEquals($options['amount'], $requestData['amt']);
        $this->assertEquals($options['notify_url'], $requestData['merchUrl']);
        $this->assertEquals($options['merchant_extra']['platformID'], $requestData['platformID']);
        $this->assertEquals('39f534fc765c7c24edd4e143b123f28c', $requestData['signMsg']);
        $this->assertEquals('5', $requestData['choosePayType']);
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

        $chengXinYunPay = new ChengXinYunPay();
        $chengXinYunPay->verifyOrderPayment([]);
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

        $chengXinYunPay = new ChengXinYunPay();
        $chengXinYunPay->setPrivateKey('test');
        $chengXinYunPay->verifyOrderPayment([]);
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
            'notifyTime' => '20180301121752',
            'tradeAmt' => '1.00',
            'merchNo' => '2105411515393243',
            'merchParam' => '',
            'orderNo' => '201803010000009807',
            'tradeDate' => '20180301',
            'accNo' => '1096906400930529689681164',
            'accDate' => '20170416',
            'orderStatus' => '1',
            'notifyType' => '1',
        ];

        $chengXinYunPay = new ChengXinYunPay();
        $chengXinYunPay->setPrivateKey('test');
        $chengXinYunPay->setOptions($options);
        $chengXinYunPay->verifyOrderPayment([]);
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
            'notifyTime' => '20180301121752',
            'tradeAmt' => '1.00',
            'merchNo' => '2105411515393243',
            'merchParam' => '',
            'orderNo' => '201803010000009807',
            'tradeDate' => '20180301',
            'accNo' => '1096906400930529689681164',
            'accDate' => '20170416',
            'orderStatus' => '1',
            'signMsg' => 'aaaaaaa',
            'notifyType' => '1',
        ];

        $chengXinYunPay = new ChengXinYunPay();
        $chengXinYunPay->setPrivateKey('test');
        $chengXinYunPay->setOptions($options);
        $chengXinYunPay->verifyOrderPayment([]);
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
            'notifyTime' => '20180301121752',
            'tradeAmt' => '1.00',
            'merchNo' => '2105411515393243',
            'merchParam' => '',
            'orderNo' => '201803010000009807',
            'tradeDate' => '20180301',
            'accNo' => '1096906400930529689681164',
            'accDate' => '20170416',
            'orderStatus' => '0',
            'signMsg' => '43fa429b91fee4da94b1fc1960c4689a',
            'notifyType' => '1',
        ];

        $chengXinYunPay = new ChengXinYunPay();
        $chengXinYunPay->setPrivateKey('test');
        $chengXinYunPay->setOptions($options);
        $chengXinYunPay->verifyOrderPayment([]);
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
            'notifyTime' => '20180301121752',
            'tradeAmt' => '1.00',
            'merchNo' => '2105411515393243',
            'merchParam' => '',
            'orderNo' => '201803010000009807',
            'tradeDate' => '20180301',
            'accNo' => '1096906400930529689681164',
            'accDate' => '20170416',
            'orderStatus' => '2',
            'signMsg' => '1600096d907332a81db7d1c87cd39c3c',
            'notifyType' => '1',
        ];

        $chengXinYunPay = new ChengXinYunPay();
        $chengXinYunPay->setPrivateKey('test');
        $chengXinYunPay->setOptions($options);
        $chengXinYunPay->verifyOrderPayment([]);
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
            'notifyTime' => '20180301121752',
            'tradeAmt' => '1.00',
            'merchNo' => '2105411515393243',
            'merchParam' => '',
            'orderNo' => '201803010000009807',
            'tradeDate' => '20180301',
            'accNo' => '1096906400930529689681164',
            'accDate' => '20170416',
            'orderStatus' => '1',
            'signMsg' => '14bfd653cf35be489053ff88c9cd9e4f',
            'notifyType' => '1',
        ];

        $entry = ['id' => '2016120800000001285'];

        $chengXinYunPay = new ChengXinYunPay();
        $chengXinYunPay->setPrivateKey('test');
        $chengXinYunPay->setOptions($options);
        $chengXinYunPay->verifyOrderPayment($entry);
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
            'notifyTime' => '20180301121752',
            'tradeAmt' => '1.00',
            'merchNo' => '2105411515393243',
            'merchParam' => '',
            'orderNo' => '201803010000009807',
            'tradeDate' => '20180301',
            'accNo' => '1096906400930529689681164',
            'accDate' => '20170416',
            'orderStatus' => '1',
            'signMsg' => '14bfd653cf35be489053ff88c9cd9e4f',
            'notifyType' => '1',
        ];

        $entry = [
            'id' => '201803010000009807',
            'amount' => '10.00',
        ];

        $chengXinYunPay = new ChengXinYunPay();
        $chengXinYunPay->setPrivateKey('test');
        $chengXinYunPay->setOptions($options);
        $chengXinYunPay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果
     */
    public function testReturnOrder()
    {
        $options = [
            'apiName' => 'PAY_RESULT_NOTIFY',
            'notifyTime' => '20180301121752',
            'tradeAmt' => '1.00',
            'merchNo' => '2105411515393243',
            'merchParam' => '',
            'orderNo' => '201803010000009807',
            'tradeDate' => '20180301',
            'accNo' => '1096906400930529689681164',
            'accDate' => '20170416',
            'orderStatus' => '1',
            'signMsg' => '14bfd653cf35be489053ff88c9cd9e4f',
            'notifyType' => '1',
        ];

        $entry = [
            'id' => '201803010000009807',
            'amount' => '1.00',
        ];

        $chengXinYunPay = new ChengXinYunPay();
        $chengXinYunPay->setPrivateKey('test');
        $chengXinYunPay->setOptions($options);
        $chengXinYunPay->verifyOrderPayment($entry);

        $this->assertEquals('SUCCESS', $chengXinYunPay->getMsg());
    }
}
