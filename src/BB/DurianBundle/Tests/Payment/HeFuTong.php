<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\HeFuTong;

class HeFuTongTest extends DurianTestCase
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

        $heFuTong = new HeFuTong();
        $heFuTong->getVerifyData();
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

        $heFuTong = new HeFuTong();
        $heFuTong->setPrivateKey('test');
        $heFuTong->getVerifyData();
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
            'number' => '10074',
            'notify_url' => 'http://www.hefut.com/',
            'paymentVendorId' => '999',
            'orderId' => '201706050000006534',
            'amount' => '0.10',
            'orderCreateDate' => '2017-06-06 10:06:06',
        ];

        $heFuTong = new HeFuTong();
        $heFuTong->setPrivateKey('test');
        $heFuTong->setOptions($options);
        $heFuTong->getVerifyData();
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $options = [
            'notify_url' => 'http://www.hefut.com/',
            'paymentVendorId' => '1092',
            'orderId' => '201801240000008862',
            'amount' => '1.00',
            'number' => '10074',
            'orderCreateDate' => '2018-01-24 09:13:40',
        ];

        $heFuTong = new HeFuTong();
        $heFuTong->setPrivateKey('test');
        $heFuTong->setOptions($options);
        $encodeData = $heFuTong->getVerifyData();

        $this->assertEquals('10074', $encodeData['pay_memberid']);
        $this->assertEquals('201801240000008862', $encodeData['pay_orderid']);
        $this->assertEquals('1.00', $encodeData['pay_amount']);
        $this->assertEquals('2018-01-24 09:13:40', $encodeData['pay_applydate']);
        $this->assertEquals('ALIPAY', $encodeData['pay_bankcode']);
        $this->assertEquals('http://www.hefut.com/', $encodeData['pay_notifyurl']);
        $this->assertEquals('http://www.hefut.com/', $encodeData['pay_callbackurl']);
        $this->assertEquals('YYzfbpc', $encodeData['tongdao']);
        $this->assertEquals('895DBB3853C4A05EAC0BA9AFA143707F', $encodeData['pay_md5sign']);
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

        $heFuTong = new HeFuTong();
        $heFuTong->verifyOrderPayment([]);
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

        $heFuTong = new HeFuTong();
        $heFuTong->setPrivateKey('test');
        $heFuTong->verifyOrderPayment([]);
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

        $options = [
            'memberid' => '10074',
            'orderid' => '201805290000001212',
            'amount' => '0.100',
            'datetime' => '20180529172646',
            'returncode' => '00',
        ];

        $heFuTong = new HeFuTong();
        $heFuTong->setPrivateKey('test');
        $heFuTong->setOptions($options);
        $heFuTong->verifyOrderPayment([]);
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
            'memberid' => '10074',
            'orderid' => '201805290000001212',
            'amount' => '0.100',
            'datetime' => '20180529172646',
            'returncode' => '00',
            'sign' => '12345679',
        ];

        $heFuTong = new HeFuTong();
        $heFuTong->setPrivateKey('test');
        $heFuTong->setOptions($options);
        $heFuTong->verifyOrderPayment([]);
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
            'memberid' => '10074',
            'orderid' => '201805290000001212',
            'amount' => '0.100',
            'datetime' => '20180529172646',
            'returncode' => '99',
            'sign' => '3C9310E0F49A6C2567CFA3E5AFD7F6E9',
        ];

        $heFuTong = new HeFuTong();
        $heFuTong->setPrivateKey('test');
        $heFuTong->setOptions($options);
        $heFuTong->verifyOrderPayment([]);
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
            'memberid' => '10074',
            'orderid' => '201805290000001212',
            'amount' => '0.100',
            'datetime' => '20180529172646',
            'returncode' => '00',
            'sign' => '44F3AF618318B514D924B9BC71ACA82F',
        ];

        $entry = ['id' => '201706050000001234'];

        $heFuTong = new HeFuTong();
        $heFuTong->setPrivateKey('test');
        $heFuTong->setOptions($options);
        $heFuTong->verifyOrderPayment($entry);
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
            'memberid' => '10074',
            'orderid' => '201805290000001212',
            'amount' => '0.100',
            'datetime' => '20180529172646',
            'returncode' => '00',
            'sign' => '44F3AF618318B514D924B9BC71ACA82F',
        ];

        $entry = [
            'id' => '201805290000001212',
            'amount' => '15.00',
        ];

        $heFuTong = new HeFuTong();
        $heFuTong->setPrivateKey('test');
        $heFuTong->setOptions($options);
        $heFuTong->verifyOrderPayment($entry);
    }

    /**
     * 測試返回驗證成功
     */
    public function testReturnSuccess()
    {
        $options = [
            'memberid' => '10074',
            'orderid' => '201805290000001212',
            'amount' => '0.100',
            'datetime' => '20180529172646',
            'returncode' => '00',
            'sign' => '44F3AF618318B514D924B9BC71ACA82F',
        ];

        $entry = [
            'id' => '201805290000001212',
            'amount' => '0.1',
        ];

        $heFuTong = new HeFuTong();
        $heFuTong->setPrivateKey('test');
        $heFuTong->setOptions($options);
        $heFuTong->verifyOrderPayment($entry);

        $this->assertEquals('OK', $heFuTong->getMsg());
    }
}
