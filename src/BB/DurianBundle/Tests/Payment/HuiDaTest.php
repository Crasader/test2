<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\HuiDa;

class HuiDaTest extends DurianTestCase
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

        $huiDa = new HuiDa();
        $huiDa->getVerifyData();
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

        $huiDa = new HuiDa();
        $huiDa->setPrivateKey('test');
        $huiDa->getVerifyData();
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
            'number' => '210001110012969',
            'orderId' => '201503160000002219',
            'orderCreateDate' => '2016-05-27 11:27:12',
            'amount' => '100',
            'notify_url' => 'http://two123.comxa.com/',
            'paymentVendorId' => '99',
            'username' => 'php1test',
            'ip' => '192.168.0.100',
        ];

        $huiDa = new HuiDa();
        $huiDa->setPrivateKey('test');
        $huiDa->setOptions($options);
        $huiDa->getVerifyData();
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
            'number' => '210001110012969',
            'orderId' => '201712250000008250',
            'orderCreateDate' => '2016-05-27 11:27:12',
            'amount' => '100',
            'notify_url' => 'http://two123.comxa.com/',
            'paymentVendorId' => '1',
            'merchant_extra' => [],
            'username' => 'php1test',
            'ip' => '192.168.0.100',
        ];

        $huiDa = new HuiDa();
        $huiDa->setPrivateKey('test');
        $huiDa->setOptions($options);
        $huiDa->getVerifyData();
    }

    /**
     * 測試加密
     */
    public function testPay()
    {
        $options = [
            'number' => '210001110012969',
            'orderId' => '201712250000008250',
            'orderCreateDate' => '2016-05-27 11:27:12',
            'amount' => '100',
            'notify_url' => 'http://two123.comxa.com/',
            'paymentVendorId' => '1',
            'merchant_extra' => ['platformID' => '210001110012969'],
            'username' => 'php1test',
            'ip' => '192.168.0.100',
        ];

        $huiDa = new HuiDa();
        $huiDa->setPrivateKey('test');
        $huiDa->setOptions($options);
        $requestData = $huiDa->getVerifyData();

        $this->assertEquals('WEB_PAY_B2C', $requestData['apiName']);
        $this->assertEquals('1.0.0.0', $requestData['apiVersion']);
        $this->assertEquals('210001110012969', $requestData['platformID']);
        $this->assertEquals('210001110012969', $requestData['merchNo']);
        $this->assertEquals('201712250000008250', $requestData['orderNo']);
        $this->assertEquals('20160527', $requestData['tradeDate']);
        $this->assertEquals('100', $requestData['amt']);
        $this->assertEquals('http://two123.comxa.com/', $requestData['merchUrl']);
        $this->assertEquals('', $requestData['merchParam']);
        $this->assertEquals('php1test', $requestData['tradeSummary']);
        $this->assertEquals('f28c996561cf3080b20ebba0cf4dc3f6', $requestData['signMsg']);
        $this->assertEquals('ICBC', $requestData['bankCode']);
        $this->assertEquals('1', $requestData['choosePayType']);
    }

    /**
     * 測試支付銀行為二維
     */
    public function testPayWithQrcode()
    {
        $options = [
            'number' => '210001110012969',
            'orderId' => '201712250000008250',
            'orderCreateDate' => '2016-05-27 11:27:12',
            'amount' => '100',
            'notify_url' => 'http://two123.comxa.com/',
            'paymentVendorId' => '1103',
            'merchant_extra' => ['platformID' => '210001110012969'],
            'username' => 'php1test',
            'ip' => '192.168.0.100',
            'verify_url' => 'payment.http.trade.dxgpay.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $huiDa = new HuiDa();
        $huiDa->setPrivateKey('test');
        $huiDa->setOptions($options);
        $requestData = $huiDa->getVerifyData();

        $this->assertEquals('WEB_PAY_B2C', $requestData['apiName']);
        $this->assertEquals('1.0.0.0', $requestData['apiVersion']);
        $this->assertEquals('210001110012969', $requestData['platformID']);
        $this->assertEquals('210001110012969', $requestData['merchNo']);
        $this->assertEquals('201712250000008250', $requestData['orderNo']);
        $this->assertEquals('20160527', $requestData['tradeDate']);
        $this->assertEquals('100', $requestData['amt']);
        $this->assertEquals('http://two123.comxa.com/', $requestData['merchUrl']);
        $this->assertEquals('', $requestData['merchParam']);
        $this->assertEquals('php1test', $requestData['tradeSummary']);
        $this->assertEquals('f28c996561cf3080b20ebba0cf4dc3f6', $requestData['signMsg']);
        $this->assertEquals('', $requestData['bankCode']);
        $this->assertEquals('6', $requestData['choosePayType']);
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

        $huiDa = new HuiDa();
        $huiDa->verifyOrderPayment([]);
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

        $huiDa = new HuiDa();
        $huiDa->setPrivateKey('test');
        $huiDa->verifyOrderPayment([]);
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
            'notifyTime' => '20171222173752',
            'tradeAmt' => '0.01',
            'merchNo' => '210001110012969',
            'merchParam' => '',
            'orderNo' => '201712220000008233',
            'tradeDate' => '20171222',
            'accNo' => '1196',
            'accDate' => '20171222',
            'orderStatus' => '1',
            'notifyType' => '1',
        ];

        $huiDa = new HuiDa();
        $huiDa->setPrivateKey('test');
        $huiDa->setOptions($options);
        $huiDa->verifyOrderPayment([]);
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
            'notifyTime' => '20171222173752',
            'tradeAmt' => '0.01',
            'merchNo' => '210001110012969',
            'merchParam' => '',
            'orderNo' => '201712220000008233',
            'tradeDate' => '20171222',
            'accNo' => '1196',
            'accDate' => '20171222',
            'orderStatus' => '1',
            'signMsg' => '80B9A254C11B629732BD197AE82DFB14',
            'notifyType' => '1',
        ];

        $huiDa = new HuiDa();
        $huiDa->setPrivateKey('test');
        $huiDa->setOptions($options);
        $huiDa->verifyOrderPayment([]);
    }

    /**
     * 測試返回訂單未支付
     */
    public function testReturnButUnpaidOrder()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Unpaid order',
            180062
        );

        $options = [
            'apiName' => 'PAY_RESULT_NOTIFY',
            'notifyTime' => '20171222173752',
            'tradeAmt' => '0.01',
            'merchNo' => '210001110012969',
            'merchParam' => '',
            'orderNo' => '201712220000008233',
            'tradeDate' => '20171222',
            'accNo' => '1196',
            'accDate' => '20171222',
            'orderStatus' => '0',
            'signMsg' => '90f9b59139f631c3b5d834cdc3b999fd',
            'notifyType' => '1',
        ];

        $huiDa = new HuiDa();
        $huiDa->setPrivateKey('test');
        $huiDa->setOptions($options);
        $huiDa->verifyOrderPayment([]);
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
            'notifyTime' => '20171222173752',
            'tradeAmt' => '0.01',
            'merchNo' => '210001110012969',
            'merchParam' => '',
            'orderNo' => '201712220000008233',
            'tradeDate' => '20171222',
            'accNo' => '1196',
            'accDate' => '20171222',
            'orderStatus' => '9',
            'signMsg' => '0a9ad17ebd7e6ad11a37eb262a650b02',
            'notifyType' => '1',
        ];

        $huiDa = new HuiDa();
        $huiDa->setPrivateKey('test');
        $huiDa->setOptions($options);
        $huiDa->verifyOrderPayment([]);
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
            'notifyTime' => '20171222173752',
            'tradeAmt' => '0.01',
            'merchNo' => '210001110012969',
            'merchParam' => '',
            'orderNo' => '201712220000008233',
            'tradeDate' => '20171222',
            'accNo' => '1196',
            'accDate' => '20171222',
            'orderStatus' => '1',
            'signMsg' => '9d53c8f5f6ef11ea92d35ff098982677',
            'notifyType' => '1',
        ];

        $entry = ['id' => '201503220000000321'];

        $huiDa = new HuiDa();
        $huiDa->setPrivateKey('test');
        $huiDa->setOptions($options);
        $huiDa->verifyOrderPayment($entry);
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
            'notifyTime' => '20171222173752',
            'tradeAmt' => '0.01',
            'merchNo' => '210001110012969',
            'merchParam' => '',
            'orderNo' => '201712220000008233',
            'tradeDate' => '20171222',
            'accNo' => '1196',
            'accDate' => '20171222',
            'orderStatus' => '1',
            'signMsg' => '9d53c8f5f6ef11ea92d35ff098982677',
            'notifyType' => '1',
        ];

        $entry = [
            'id' => '201712220000008233',
            'amount' => '10.00',
        ];

        $huiDa = new HuiDa();
        $huiDa->setPrivateKey('test');
        $huiDa->setOptions($options);
        $huiDa->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果
     */
    public function testReturnOrder()
    {
        $options = [
            'apiName' => 'PAY_RESULT_NOTIFY',
            'notifyTime' => '20171222173752',
            'tradeAmt' => '0.01',
            'merchNo' => '210001110012969',
            'merchParam' => '',
            'orderNo' => '201712220000008233',
            'tradeDate' => '20171222',
            'accNo' => '1196',
            'accDate' => '20171222',
            'orderStatus' => '1',
            'signMsg' => '9d53c8f5f6ef11ea92d35ff098982677',
            'notifyType' => '1',
        ];

        $entry = [
            'id' => '201712220000008233',
            'amount' => '0.01',
        ];

        $huiDa = new HuiDa();
        $huiDa->setPrivateKey('test');
        $huiDa->setOptions($options);
        $huiDa->verifyOrderPayment($entry);

        $this->assertEquals('SUCCESS', $huiDa->getMsg());
    }
}
