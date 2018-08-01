<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\ZhongYinPay;

class ZhongYinPayTest extends DurianTestCase
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

        $zhongYinPay = new ZhongYinPay();
        $zhongYinPay->getVerifyData();
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

        $zhongYinPay = new ZhongYinPay();
        $zhongYinPay->setPrivateKey('test');
        $zhongYinPay->getVerifyData();
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
            'number' => '10051',
            'notify_url' => 'http://pay.web.my/pay/',
            'paymentVendorId' => '999',
            'orderId' => '201706050000006534',
            'amount' => '0.10',
            'orderCreateDate' => '2018-03-06 10:06:06',
            'username' => 'php1test',
        ];

        $zhongYinPay = new ZhongYinPay();
        $zhongYinPay->setPrivateKey('test');
        $zhongYinPay->setOptions($options);
        $zhongYinPay->getVerifyData();
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $options = [
            'notify_url' => 'http://pay.web.my/pay/',
            'paymentVendorId' => '1090',
            'orderId' => '2016081600000010051',
            'amount' => '1.00',
            'number' => '10051',
            'orderCreateDate' => '2018-03-06 10:06:06',
            'username' => 'php1test',
        ];

        $zhongYinPay = new ZhongYinPay();
        $zhongYinPay->setPrivateKey('test');
        $zhongYinPay->setOptions($options);
        $encodeData = $zhongYinPay->getVerifyData();

        $this->assertEquals('10051', $encodeData['pay_memberid']);
        $this->assertEquals('201608160000001005110051', $encodeData['pay_orderid']);
        $this->assertEquals('1.00', $encodeData['pay_amount']);
        $this->assertEquals('2018-03-06 10:06:06', $encodeData['pay_applydate']);
        $this->assertEquals('WXPAY', $encodeData['pay_bankcode']);
        $this->assertEquals('php1test', $encodeData['pay_productname']);
        $this->assertEquals('Qhwxsm', $encodeData['pay_tongdao']);
        $this->assertEquals('http://pay.web.my/pay/', $encodeData['pay_notifyurl']);
        $this->assertEquals('D8BFC21B6B9B035A61D710D4B8187FF8', $encodeData['pay_md5sign']);
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

        $zhongYinPay = new ZhongYinPay();
        $zhongYinPay->verifyOrderPayment([]);
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

        $zhongYinPay = new ZhongYinPay();
        $zhongYinPay->setPrivateKey('test');
        $zhongYinPay->verifyOrderPayment([]);
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
            'memberid' => '10051',
            'orderid' => '2016081600000010051',
            'amount' => '0.010',
            'datetime' => '2017-06-05 17:17:14',
            'returncode' => '00',
        ];

        $zhongYinPay = new ZhongYinPay();
        $zhongYinPay->setPrivateKey('test');
        $zhongYinPay->setOptions($options);
        $zhongYinPay->verifyOrderPayment([]);
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
            'memberid' => '10051',
            'orderid' => '2016081600000010051',
            'amount' => '0.010',
            'datetime' => '2017-06-05 17:17:14',
            'returncode' => '00',
            'sign' => '123456798798',
        ];

        $zhongYinPay = new ZhongYinPay();
        $zhongYinPay->setPrivateKey('test');
        $zhongYinPay->setOptions($options);
        $zhongYinPay->verifyOrderPayment([]);
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
            'memberid' => '10051',
            'orderid' => '2016081600000010051',
            'amount' => '0.010',
            'datetime' => '2017-06-05 17:17:14',
            'returncode' => '99',
            'sign' => '8B0D18CCB824ADEBCD71C9F5022F3FC1',
        ];

        $zhongYinPay = new ZhongYinPay();
        $zhongYinPay->setPrivateKey('test');
        $zhongYinPay->setOptions($options);
        $zhongYinPay->verifyOrderPayment([]);
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
            'memberid' => '10051',
            'orderid' => '2016081600000010051',
            'amount' => '0.010',
            'datetime' => '2017-06-05 17:17:14',
            'returncode' => '00',
            'sign' => 'E923895D87436DFEFEAE42B40142C84C',
        ];

        $entry = ['id' => '201706050000001234'];

        $zhongYinPay = new ZhongYinPay();
        $zhongYinPay->setPrivateKey('test');
        $zhongYinPay->setOptions($options);
        $zhongYinPay->verifyOrderPayment($entry);
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
            'memberid' => '10051',
            'orderid' => '2016081600000010051',
            'amount' => '0.010',
            'datetime' => '2017-06-05 17:17:14',
            'returncode' => '00',
            'sign' => 'E923895D87436DFEFEAE42B40142C84C',
        ];

        $entry = [
            'id' => '20160816000000',
            'amount' => '15.00',
        ];

        $zhongYinPay = new ZhongYinPay();
        $zhongYinPay->setPrivateKey('test');
        $zhongYinPay->setOptions($options);
        $zhongYinPay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回驗證成功
     */
    public function testReturnSuccess()
    {
        $options = [
            'memberid' => '10051',
            'orderid' => '2016081600000010051',
            'amount' => '0.010',
            'datetime' => '2017-06-05 17:17:14',
            'returncode' => '00',
            'sign' => 'E923895D87436DFEFEAE42B40142C84C',
        ];

        $entry = [
            'id' => '20160816000000',
            'amount' => '0.01',
        ];

        $zhongYinPay = new ZhongYinPay();
        $zhongYinPay->setPrivateKey('test');
        $zhongYinPay->setOptions($options);
        $zhongYinPay->verifyOrderPayment($entry);

        $this->assertEquals('ok', $zhongYinPay->getMsg());
    }
}
