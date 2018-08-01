<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\ZhanYiFu;

class ZhanYiFuTest extends DurianTestCase
{
    /**
     * 訂單參數
     *
     * @var array
     */
    private $option;

    /**
     * 返回結果
     *
     * @var array
     */
    private $returnResult;

    public function setUp()
    {
        parent::setUp();

        $this->option = [
            'number' => '9453',
            'orderCreateDate' => '2018-03-23 03:24:19',
            'orderId' => '201803230000009453',
            'notify_url' => 'http://www.seafood.help/',
            'amount' => '1.9453',
            'paymentVendorId' => '1102',
        ];

        $this->returnResult = [
            'sign' => 'F162A48C90E1CC804B5880609604CDE8',
            'pay_money' => '0.01',
            'pay_type' => '1',
            'order_num' => '201803220000001002',
            'merchant_num' => 'F201803098356',
            'datetime' => '2018-03-22 18:38:08',
            'order_status' => '1',
            'mer_rmk' => '',
            'passback_params' => '',
        ];
    }

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

        $zhanYiFu = new ZhanYiFu();
        $zhanYiFu->getVerifyData();
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

        $zhanYiFu = new ZhanYiFu();
        $zhanYiFu->setPrivateKey('test');
        $zhanYiFu->setOptions([]);
        $zhanYiFu->getVerifyData();
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

        $this->option['paymentVendorId'] = '9999';

        $zhanYiFu = new ZhanYiFu();
        $zhanYiFu->setPrivateKey('test');
        $zhanYiFu->setOptions($this->option);
        $zhanYiFu->getVerifyData();
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $zhanYiFu = new ZhanYiFu();
        $zhanYiFu->setPrivateKey('test');
        $zhanYiFu->setOptions($this->option);
        $encodeData = $zhanYiFu->getVerifyData();

        $this->assertEquals('9453', $encodeData['merchant_num']);
        $this->assertEquals('201803230000009453', $encodeData['order_num']);
        $this->assertEquals('2018-03-23 03:24:19', $encodeData['datetime']);
        $this->assertEquals('201803230000009453', $encodeData['title']);
        $this->assertEquals('201803230000009453', $encodeData['body']);
        $this->assertEquals('1.95', $encodeData['pay_money']);
        $this->assertEquals('http://www.seafood.help/', $encodeData['notify_url']);
        $this->assertEquals('http://www.seafood.help/', $encodeData['return_url']);
        $this->assertEquals('82889A23F2FAA4888411BC3738C4F4E5', $encodeData['sign']);
        $this->assertEquals('3', $encodeData['pay_type']);
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

        $zhanYiFu = new ZhanYiFu();
        $zhanYiFu->verifyOrderPayment([]);
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

        $zhanYiFu = new ZhanYiFu();
        $zhanYiFu->setPrivateKey('test');
        $zhanYiFu->verifyOrderPayment([]);
    }

    /**
     * 測試返回時缺少加密簽名
     */
    public function testReturnWithoutSignature()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        unset($this->returnResult['sign']);

        $zhanYiFu = new ZhanYiFu();
        $zhanYiFu->setPrivateKey('test');
        $zhanYiFu->setOptions($this->returnResult);
        $zhanYiFu->verifyOrderPayment([]);
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

        $this->returnResult['sign'] = '9453';

        $zhanYiFu = new ZhanYiFu();
        $zhanYiFu->setPrivateKey('test');
        $zhanYiFu->setOptions($this->returnResult);
        $zhanYiFu->verifyOrderPayment([]);
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

        $this->returnResult['order_status'] = '9453';
        $this->returnResult['sign'] = '49D03A2F8205B568C3014C8F6F65B6AB';

        $zhanYiFu = new ZhanYiFu();
        $zhanYiFu->setPrivateKey('test');
        $zhanYiFu->setOptions($this->returnResult);
        $zhanYiFu->verifyOrderPayment([]);
    }

    /**
     * 測試返回時單號不正確
     */
    public function testReturnPaymentOrderIdError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $entry = ['id' => '201803230000009487'];

        $zhanYiFu = new ZhanYiFu();
        $zhanYiFu->setPrivateKey('test');
        $zhanYiFu->setOptions($this->returnResult);
        $zhanYiFu->verifyOrderPayment($entry);
    }

    /**
     * 測試返回時金額不正確
     */
    public function testReturnPaymentOrderAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $entry = [
            'id' => '201803220000001002',
            'amount' => '999',
        ];

        $zhanYiFu = new ZhanYiFu();
        $zhanYiFu->setPrivateKey('test');
        $zhanYiFu->setOptions($this->returnResult);
        $zhanYiFu->verifyOrderPayment($entry);
    }

    /**
     * 測試返回時支付成功
     */
    public function testReturnResultSuccess()
    {
        $entry = [
            'id' => '201803220000001002',
            'amount' => '0.01',
        ];

        $zhanYiFu = new ZhanYiFu();
        $zhanYiFu->setPrivateKey('test');
        $zhanYiFu->setOptions($this->returnResult);
        $zhanYiFu->verifyOrderPayment($entry);

        $this->assertEquals('SUCCESS', $zhanYiFu->getMsg());
    }
}
