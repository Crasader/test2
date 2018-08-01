<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\HuiJie;
use Buzz\Message\Response;

class HuiJieTest extends DurianTestCase
{
    /**
     * 測試支付時沒有私鑰
     */
    public function testPayWithoutKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $huiJie = new HuiJie();
        $huiJie->getVerifyData();
    }

    /**
     * 測試支付時未指定支付參數
     */
    public function testPayWithNoPayParameterSpecefied()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No pay parameter specified',
            180145
        );

        $huiJie = new HuiJie();
        $huiJie->setPrivateKey('test');
        $huiJie->getVerifyData();
    }

    /**
     * 測試支付時帶入不支援的銀行
     */
    public function testPayWithUnsupportedPaymentVendor()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'PaymentVendor is not supported by PaymentGateway',
            180066
        );

        $option = [
            'number' => '9453',
            'amount' => '1.9543',
            'notify_url' => 'http://www.seafood.help/',
            'orderId' => '201801160000009543',
            'paymentVendorId' => '1',
        ];

        $huiJie = new HuiJie();
        $huiJie->setPrivateKey('test');
        $huiJie->setOptions($option);
        $huiJie->getVerifyData();
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $option = [
            'number' => '9453',
            'amount' => '1.9453',
            'notify_url' => 'http://www.seafood.help/',
            'orderId' => '201801160000009453',
            'paymentVendorId' => '1092',
        ];

        $huiJie = new HuiJie();
        $huiJie->setPrivateKey('test');
        $huiJie->setOptions($option);
        $encodeData = $huiJie->getVerifyData();

        $this->assertEquals('9453', $encodeData['merNo']);
        $this->assertEquals('1.9453', $encodeData['amount']);
        $this->assertEquals('http://www.seafood.help/', $encodeData['notifyUrl']);
        $this->assertEquals('http://www.seafood.help/', $encodeData['returnUrl']);
        $this->assertEquals('201801160000009453', $encodeData['orderNo']);
        $this->assertEquals('ALIPAY', $encodeData['payType']);
        $this->assertEquals('49bbe5d11ab8225d5f320916aecaf61d', $encodeData['sign']);
    }

    /**
     * 測試返回時沒有私鑰
     */
    public function testReturnWithoutKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $huiJie = new HuiJie();
        $huiJie->verifyOrderPayment([]);
    }

    /**
     * 測試返回時未指定參數
     */
    public function testReturnWithNoParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $huiJie = new HuiJie();
        $huiJie->setPrivateKey('test');
        $huiJie->verifyOrderPayment([]);
    }

    /**
     * 測試返回時缺少加密簽名
     */
    public function testReturnWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $option = [
            'payoverTime' => '2018-01-16 11:11:11',
            'orderNo' => '201801160000009453',
            'orderAmount' => 1.9453,
            'status' => 200,
            'payType' => 'ALIPAY',
            'orderStatus' => 'SUCCESS',
        ];

        $huiJie = new HuiJie();
        $huiJie->setPrivateKey('test');
        $huiJie->setOptions($option);
        $huiJie->verifyOrderPayment([]);
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

        $option = [
            'sign' => '9453',
            'payoverTime' => '2018-01-16 11:11:11',
            'orderNo' => '201801160000009453',
            'orderAmount' => 1.9453,
            'status' => 200,
            'payType' => 'ALIPAY',
            'orderStatus' => 'SUCCESS',
        ];

        $huiJie = new HuiJie();
        $huiJie->setPrivateKey('test');
        $huiJie->setOptions($option);
        $huiJie->verifyOrderPayment([]);
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

        $option = [
            'sign' => 'b8e5b48a0b59835026d8f8d5be3f3c7d',
            'payoverTime' => '2018-01-16 11:11:11',
            'orderNo' => '201801160000009453',
            'orderAmount' => 1.9453,
            'status' => 200,
            'payType' => 'ALIPAY',
            'orderStatus' => 'FAIL',
        ];

        $huiJie = new HuiJie();
        $huiJie->setPrivateKey('test');
        $huiJie->setOptions($option);
        $huiJie->verifyOrderPayment([]);
    }

    /**
     * 測試返回時單號不正確
     */
    public function testReturnOrderIdError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $option = [
            'sign' => '6a271bf5fe32230cb728709750eebe9f',
            'payoverTime' => '2018-01-16 11:11:11',
            'orderNo' => '201801160000009453',
            'orderAmount' => 1.9453,
            'status' => 200,
            'payType' => 'ALIPAY',
            'orderStatus' => 'SUCCESS',
        ];

        $entry = ['id' => '201801160000009487'];

        $huiJie = new HuiJie();
        $huiJie->setPrivateKey('test');
        $huiJie->setOptions($option);
        $huiJie->verifyOrderPayment($entry);
    }

    /**
     * 測試返回時金額不正確
     */
    public function testReturnOrderAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $option = [
            'sign' => '6a271bf5fe32230cb728709750eebe9f',
            'payoverTime' => '2018-01-16 11:11:11',
            'orderNo' => '201801160000009453',
            'orderAmount' => 1.9453,
            'status' => 200,
            'payType' => 'ALIPAY',
            'orderStatus' => 'SUCCESS',
        ];

        $entry = [
            'id' => '201801160000009453',
            'amount' => 1.9487,
        ];

        $huiJie = new HuiJie();
        $huiJie->setPrivateKey('test');
        $huiJie->setOptions($option);
        $huiJie->verifyOrderPayment($entry);
    }

    /**
     * 測試返回支付成功
     */
    public function testReturnResultSuccess()
    {
        $option = [
            'sign' => '6a271bf5fe32230cb728709750eebe9f',
            'payoverTime' => '2018-01-16 11:11:11',
            'orderNo' => '201801160000009453',
            'orderAmount' => 1.9453,
            'status' => 200,
            'payType' => 'ALIPAY',
            'orderStatus' => 'SUCCESS',
        ];

        $entry = [
            'id' => '201801160000009453',
            'amount' => 1.9453,
        ];

        $huiJie = new HuiJie();
        $huiJie->setPrivateKey('test');
        $huiJie->setOptions($option);
        $huiJie->verifyOrderPayment($entry);

        $this->assertEquals('SUCCESS', $huiJie->getMsg());
    }
}
