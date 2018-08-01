<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\ShiuanYuanPay;

class ShiuanYuanPayTest extends DurianTestCase
{
    /**
     * 提交給支付平台時需要的參數
     *
     * @var array
     */
    private $sourceData;

    /**
     * 支付成功時通知的參數
     *
     * @var array
     */
    private $returnResult;

    public function setUp()
    {
        parent::setUp();

        $this->sourceData = [
            'number' => '18002000',
            'amount' => '1',
            'orderId' => '201806210000014414',
            'paymentVendorId' => '1092',
            'notify_url' => 'http://fufutest.000webhostapp.com/pay/',
        ];

        $this->returnResult = [
            'merId' => '18002000',
            'merOrderNo' => '201806210000014414',
            'orderAmt' => '1',
            'payDate' => '2018-06-21',
            'payNo' => 'XY201806212027071248',
            'payStatus' => 'S',
            'payTime' => '20:27:39',
            'realAmt' => '0.99',
            'version' => '1.0.0',
            'sign' => '161f149dfc7a2ff2d260bf00c7d05a27',
        ];
    }

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

        $shiuanYuanPay = new ShiuanYuanPay();
        $shiuanYuanPay->getVerifyData();
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

        $shiuanYuanPay = new ShiuanYuanPay();
        $shiuanYuanPay->setPrivateKey('test');
        $shiuanYuanPay->getVerifyData();
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

        $this->sourceData['paymentVendorId'] = '9999';

        $shiuanYuanPay = new ShiuanYuanPay();
        $shiuanYuanPay->setPrivateKey('test');
        $shiuanYuanPay->setOptions($this->sourceData);
        $shiuanYuanPay->getVerifyData();
    }

    /**
     * 測試二維支付
     */
    public function testQrcodePay()
    {
        $shiuanYuanPay = new ShiuanYuanPay();
        $shiuanYuanPay->setPrivateKey('test');
        $shiuanYuanPay->setOptions($this->sourceData);
        $verifyData = $shiuanYuanPay->getVerifyData();

        $result = [
            'version' => '1.0.0',
            'merId' => '18002000',
            'merOrderNo' => '201806210000014414',
            'orderAmt' => '1.00',
            'payPlat' => 'alipay',
            'notifyUrl' => 'http://fufutest.000webhostapp.com/pay/',
            'callbackUrl' => '',
            'sign' => 'f7dfa00d0e29e38f6f928fb093ee5ffd',
        ];

        $this->assertEquals(json_encode($result), $verifyData['param']);
        $this->assertEquals('GET', $shiuanYuanPay->getPayMethod());
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

        $shiuanYuanPay = new ShiuanYuanPay();
        $shiuanYuanPay->verifyOrderPayment([]);
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

        $shiuanYuanPay = new ShiuanYuanPay();
        $shiuanYuanPay->setPrivateKey('test');
        $shiuanYuanPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時缺少簽名
     */
    public function testReturnWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        unset($this->returnResult['sign']);

        $shiuanYuanPay = new ShiuanYuanPay();
        $shiuanYuanPay->setPrivateKey('test');
        $shiuanYuanPay->setOptions($this->returnResult);
        $shiuanYuanPay->verifyOrderPayment([]);
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

        $shiuanYuanPay = new ShiuanYuanPay();
        $shiuanYuanPay->setPrivateKey('test');
        $shiuanYuanPay->setOptions($this->returnResult);
        $shiuanYuanPay->verifyOrderPayment([]);
    }

    /**
    * 測試返回時支付失敗
    */
    public function testReturnWithPaymentFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $this->returnResult['payStatus'] = '0';
        $this->returnResult['sign'] = 'ac1077bb7a159ac387a9a24bd102eb92';

        $shiuanYuanPay = new ShiuanYuanPay();
        $shiuanYuanPay->setPrivateKey('test');
        $shiuanYuanPay->setOptions($this->returnResult);
        $shiuanYuanPay->verifyOrderPayment([]);
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

        $this->returnResult['payStatus'] = 'B';
        $this->returnResult['sign'] = '8f6108f131d69df106d3bca9e7cc2e0b';

        $entry = ['id' => '201503220000000555'];

        $shiuanYuanPay = new ShiuanYuanPay();
        $shiuanYuanPay->setPrivateKey('test');
        $shiuanYuanPay->setOptions($this->returnResult);
        $shiuanYuanPay->verifyOrderPayment($entry);
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

        $entry = [
            'id' => '201806210000014414',
            'amount' => '15.00',
        ];

        $shiuanYuanPay = new ShiuanYuanPay();
        $shiuanYuanPay->setPrivateKey('test');
        $shiuanYuanPay->setOptions($this->returnResult);
        $shiuanYuanPay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果
     */
    public function testReturnSuccess()
    {
        $entry = [
            'id' => '201806210000014414',
            'amount' => '1.00',
        ];

        $shiuanYuanPay = new ShiuanYuanPay();
        $shiuanYuanPay->setPrivateKey('test');
        $shiuanYuanPay->setOptions($this->returnResult);
        $shiuanYuanPay->verifyOrderPayment($entry);

        $this->assertEquals('success', $shiuanYuanPay->getMsg());
    }
}
