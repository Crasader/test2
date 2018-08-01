<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Payment\RainbowNetwork;
use BB\DurianBundle\Tests\DurianTestCase;

class RainbowNetworkTest extends DurianTestCase
{
    /**
     * 測試支付時缺少私鑰
     */
    public function testPayWithoutPrivate()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $rainbowNetwork = new RainbowNetwork();
        $rainbowNetwork->getVerifyData();
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

        $rainbowNetwork = new RainbowNetwork();
        $rainbowNetwork->setPrivateKey('test');
        $rainbowNetwork->getVerifyData();

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
            'notify_url' => 'http://po-wei.com.tw',
            'paymentVendorId' => '1',
            'orderId' => '201708290000009527',
            'amount' => '0.01',
        ];

        $rainbowNetwork = new RainbowNetwork();
        $rainbowNetwork->setPrivateKey('test');
        $rainbowNetwork->setOptions($sourceData);
        $rainbowNetwork->getVerifyData();
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $sourceData = [
            'number' => '9527',
            'paymentVendorId' => '1090',
            'amount' => '1.00',
            'orderId' => '201708290000009527',
            'notify_url' => 'http://po-wei.com.tw',
        ];

        $rainbowNetwork = new RainbowNetwork();
        $rainbowNetwork->setPrivateKey('test');
        $rainbowNetwork->setOptions($sourceData);
        $encodeData = $rainbowNetwork->getVerifyData();

        $this->assertEquals($sourceData['number'], $encodeData['parter']);
        $this->assertEquals('1004', $encodeData['type']);
        $this->assertEquals($sourceData['amount'], $encodeData['value']);
        $this->assertEquals($sourceData['orderId'], $encodeData['orderid']);
        $this->assertEquals($sourceData['notify_url'], $encodeData['callbackurl']);
        $this->assertEquals('', $encodeData['hrefbackurl']);
        $this->assertEquals('', $encodeData['payerIp']);
        $this->assertEquals('', $encodeData['attach']);
        $this->assertEquals('5552500f227b7464d96b627eb124e7c7', $encodeData['sign']);
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

        $rainbowNetwork = new RainbowNetwork();
        $rainbowNetwork->verifyOrderPayment([]);
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

        $rainbowNetwork = new RainbowNetwork();
        $rainbowNetwork->setPrivateKey('test');
        $rainbowNetwork->verifyOrderPayment([]);
    }

    /**
     * 測試返回時缺少sign
     */
    public function testReturnWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $sourceData = [
            'orderid' => '201708290000009527',
            'opstate' => '0',
            'ovalue' => '1.01',
            'systime' => '2017/08/29 19:16:52',
            'sysorderid' => '1708251615396990563',
            'completiontime' => '2017/08/29 19:16:53',
            'attach' => '',
            'msg' => '',
        ];

        $rainbowNetwork = new RainbowNetwork();
        $rainbowNetwork->setPrivateKey('test');
        $rainbowNetwork->setOptions($sourceData);
        $rainbowNetwork->verifyOrderPayment([]);
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
            'orderid' => '201708290000009527',
            'opstate' => '0',
            'ovalue' => '1.01',
            'systime' => '2017/08/29 19:16:52',
            'sysorderid' => '1708251615396990563',
            'completiontime' => '2017/08/29 19:16:53',
            'attach' => '',
            'msg' => '',
            'sign' => 'PsdOfWdfEsfIdIfSdfHsfAsfNdfDdfSaOdfME',
        ];

        $rainbowNetwork = new RainbowNetwork();
        $rainbowNetwork->setPrivateKey('test');
        $rainbowNetwork->setOptions($sourceData);
        $rainbowNetwork->verifyOrderPayment([]);
    }

    /**
     * 測試返回時返回請求參數無效
     */
    public function testReturnInvalidPayParameters()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Invalid pay parameters',
            180129
        );

        $sourceData = [
            'orderid' => '201708290000009527',
            'opstate' => '-1',
            'ovalue' => '0.00',
            'sign' => '0c01fb22b3ab5b6a6498378b574d2309',
        ];

        $rainbowNetwork = new RainbowNetwork();
        $rainbowNetwork->setPrivateKey('test');
        $rainbowNetwork->setOptions($sourceData);
        $rainbowNetwork->verifyOrderPayment([]);
    }

    /**
     * 測試返回時返回請求簽名錯誤
     */
    public function testReturnMerchantSignError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'PaymentGateway error, Merchant sign error',
            180127
        );

        $sourceData = [
            'orderid' => '201708290000009527',
            'opstate' => '-2',
            'ovalue' => '0.00',
            'sign' => '44d1ac5eed8e3f51f324f478f640acf5',
        ];

        $rainbowNetwork = new RainbowNetwork();
        $rainbowNetwork->setPrivateKey('test');
        $rainbowNetwork->setOptions($sourceData);
        $rainbowNetwork->verifyOrderPayment([]);
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
            'orderid' => '201708290000009527',
            'opstate' => '999',
            'ovalue' => '0.00',
            'sign' => '45c7d419a4cff54ce114d419df076ba5',
        ];

        $rainbowNetwork = new RainbowNetwork();
        $rainbowNetwork->setPrivateKey('test');
        $rainbowNetwork->setOptions($sourceData);
        $rainbowNetwork->verifyOrderPayment([]);
    }

    /**
     * 測試返回結果為訂單單號錯誤
     */
    public function testReturnOrderIdFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $sourceData = [
            'orderid' => '201708290000009527',
            'opstate' => '0',
            'ovalue' => '1.01',
            'systime' => '2017/08/29 19:16:52',
            'sysorderid' => '1708251615396990563',
            'completiontime' => '2017/08/29 19:16:53',
            'attach' => '',
            'msg' => '',
            'sign' => '726ea057f2ffcdb09e41169c3d630d1b',
        ];

        $entry = ['id' => '201609290000004499'];

        $rainbowNetwork = new RainbowNetwork();
        $rainbowNetwork->setPrivateKey('test');
        $rainbowNetwork->setOptions($sourceData);
        $rainbowNetwork->verifyOrderPayment($entry);
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
            'orderid' => '201708290000009527',
            'opstate' => '0',
            'ovalue' => '1.01',
            'systime' => '2017/08/29 19:16:52',
            'sysorderid' => '1708251615396990563',
            'completiontime' => '2017/08/29 19:16:53',
            'attach' => '',
            'msg' => '',
            'sign' => '726ea057f2ffcdb09e41169c3d630d1b',
        ];

        $entry = [
            'id' => '201708290000009527',
            'amount' => '15.00',
        ];

        $rainbowNetwork = new RainbowNetwork();
        $rainbowNetwork->setPrivateKey('test');
        $rainbowNetwork->setOptions($sourceData);
        $rainbowNetwork->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果
     */
    public function testReturnOrder()
    {
        $sourceData = [
            'orderid' => '201708290000009527',
            'opstate' => '0',
            'ovalue' => '1.01',
            'systime' => '2017/08/29 19:16:52',
            'sysorderid' => '1708251615396990563',
            'completiontime' => '2017/08/29 19:16:53',
            'attach' => '',
            'msg' => '',
            'sign' => '726ea057f2ffcdb09e41169c3d630d1b',
        ];

        $entry = [
            'id' => '201708290000009527',
            'amount' => '1.01',
        ];

        $rainbowNetwork = new RainbowNetwork();
        $rainbowNetwork->setPrivateKey('test');
        $rainbowNetwork->setOptions($sourceData);
        $rainbowNetwork->verifyOrderPayment($entry);

        $this->assertEquals('opstate=0', $rainbowNetwork->getMsg());
    }
}