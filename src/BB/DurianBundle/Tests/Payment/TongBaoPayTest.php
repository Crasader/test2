<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\TongBaoPay;

class TongBaoPayTest extends DurianTestCase
{
    /**
     * 提交給支付平台時需要的參數
     *
     * @var array
     */
    private $option;

    /**
     * 支付成功時通知的參數
     *
     * @var array
     */
    private $returnResult;

    public function setUp()
    {
        parent::setUp();

        $this->option = [
            'number' => '10004',
            'orderId' => '201807110000015169',
            'amount' => '1',
            'notify_url' => 'http://fufutest.000webhostapp.com/pay/',
            'paymentVendorId' => '1092',
        ];

        $this->returnResult = [
            'status' => '1',
            'customerid' => '10936',
            'sdpayno' => '2018071221123322829',
            'sdorderno' => '201807110000015169',
            'total_fee' => '0.01',
            'paytype' => 'alipay',
            'remark' => '',
            'x' => '0.00',
            'sign' => '7d06251d69d54e2e8e25444b44bcebc2',
        ];
    }

    /**
     * 測試支付時沒有帶入privateKey的情況
     */
    public function testPayNoPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $tongBaoPay = new TongBaoPay();
        $tongBaoPay->getVerifyData();
    }

    /**
     * 測試支付時未指定支付參數
     */
    public function testPayNoPayParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No pay parameter specified',
            180145
        );

        $sourceData = ['number' => ''];

        $tongBaoPay = new TongBaoPay();
        $tongBaoPay->setPrivateKey('1234');
        $tongBaoPay->setOptions($sourceData);
        $tongBaoPay->getVerifyData();
    }

    /**
     * 測試支付時帶入不支援的銀行
     */
    public function testPayNotSupportBank()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'PaymentVendor is not supported by PaymentGateway',
            180066
        );

        $this->option['paymentVendorId'] = '999';

        $tongBaoPay = new TongBaoPay();
        $tongBaoPay->setPrivateKey('1234');
        $tongBaoPay->setOptions($this->option);
        $tongBaoPay->getVerifyData();
    }

    /**
     * 測試加密
     */
    public function testPay()
    {
        $tongBaoPay = new TongBaoPay();
        $tongBaoPay->setPrivateKey('1234');
        $tongBaoPay->setOptions($this->option);
        $encodeData = $tongBaoPay->getVerifyData();

        $this->assertEquals('1.0', $encodeData['version']);
        $this->assertEquals('10004', $encodeData['customerid']);
        $this->assertEquals('201807110000015169', $encodeData['sdorderno']);
        $this->assertEquals('1.00', $encodeData['total_fee']);
        $this->assertEquals('alipay', $encodeData['paytype']);
        $this->assertEquals('http://fufutest.000webhostapp.com/pay/', $encodeData['notifyurl']);
        $this->assertEquals('http://fufutest.000webhostapp.com/pay/', $encodeData['returnurl']);
        $this->assertEquals('55214a287b3c1683dcc288a512a1cb0c', $encodeData['sign']);
    }

    /**
     * 測試返回時沒有帶入key的情況
     */
    public function testReturnWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $tongBaoPay = new TongBaoPay();
        $tongBaoPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時支付平台未指定返回參數
     */
    public function testReturnWithNoReturnParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $sourceData = [];

        $tongBaoPay = new TongBaoPay();
        $tongBaoPay->setPrivateKey('1234');
        $tongBaoPay->setOptions($sourceData);
        $tongBaoPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時沒有回傳sign
     */
    public function testReturnWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        unset($this->returnResult['sign']);

        $tongBaoPay = new TongBaoPay();
        $tongBaoPay->setPrivateKey('1234');
        $tongBaoPay->setOptions($this->returnResult);
        $tongBaoPay->verifyOrderPayment([]);
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

        $this->returnResult['sign'] = 'error';

        $tongBaoPay = new TongBaoPay();
        $tongBaoPay->setPrivateKey('1234');
        $tongBaoPay->setOptions($this->returnResult);
        $tongBaoPay->verifyOrderPayment([]);
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

        $this->returnResult['status'] = '2';
        $this->returnResult['sign'] = '777bd49861e04b5ac5e5b21b85abe990';

        $tongBaoPay = new TongBaoPay();
        $tongBaoPay->setPrivateKey('1234');
        $tongBaoPay->setOptions($this->returnResult);
        $tongBaoPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時單號不正確的情況
     */
    public function testReturnOrderIdError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $entry = ['id' => '201405020016748610'];

        $tongBaoPay = new TongBaoPay();
        $tongBaoPay->setPrivateKey('1234');
        $tongBaoPay->setOptions($this->returnResult);
        $tongBaoPay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回時金額不正確的情況
     */
    public function testReturnOrderAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $entry = [
            'id' => '201807110000015169',
            'amount' => '9900.0000',
        ];

        $tongBaoPay = new TongBaoPay();
        $tongBaoPay->setPrivateKey('1234');
        $tongBaoPay->setOptions($this->returnResult);
        $tongBaoPay->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testReturnSuccess()
    {
        $entry = [
            'id' => '201807110000015169',
            'amount' => '0.01',
        ];

        $tongBaoPay = new TongBaoPay();
        $tongBaoPay->setPrivateKey('1234');
        $tongBaoPay->setOptions($this->returnResult);
        $tongBaoPay->verifyOrderPayment($entry);

        $this->assertEquals('success', $tongBaoPay->getMsg());
    }
}
