<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\YiFuPay;

class YiFuPayTest extends DurianTestCase
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
            'number' => '9527',
            'paymentVendorId' => '1092',
            'amount' => '1.00',
            'orderId' => '201803212100009527',
            'notify_url' => 'http://www.seafood.help/',
            'username' => 'seafood',
        ];

        $this->returnResult = [
            'partner' => '9527',
            'ordernumber' => '201803212100009527',
            'orderstatus' => '1',
            'paymoney' => '1.00',
            'sysnumber' => 'yp180321111695000',
            'attach' => '',
            'sign' => '2833f36596f74c858bd26b20736d0fa9',
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

        $yiFuPay = new YiFuPay();
        $yiFuPay->getVerifyData();
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

        $yiFuPay = new YiFuPay();
        $yiFuPay->setPrivateKey('test');
        $yiFuPay->setOptions([]);
        $yiFuPay->getVerifyData();
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

        $this->option['paymentVendorId'] = '999';

        $yiFuPay = new YiFuPay();
        $yiFuPay->setPrivateKey('test');
        $yiFuPay->setOptions($this->option);
        $yiFuPay->getVerifyData();
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $yiFuPay = new YiFuPay();
        $yiFuPay->setPrivateKey('test');
        $yiFuPay->setOptions($this->option);
        $encodeData = $yiFuPay->getVerifyData();

        $this->assertEquals('3.0', $encodeData['version']);
        $this->assertEquals('Rh.online.interface', $encodeData['method']);
        $this->assertEquals('9527', $encodeData['partner']);
        $this->assertEquals('ALIPAY', $encodeData['banktype']);
        $this->assertEquals('1.00', $encodeData['paymoney']);
        $this->assertEquals('201803212100009527', $encodeData['ordernumber']);
        $this->assertEquals('http://www.seafood.help/', $encodeData['callbackurl']);
        $this->assertEquals('', $encodeData['hrefbackurl']);
        $this->assertEquals('', $encodeData['attach']);
        $this->assertEquals('1', $encodeData['isshow']);
        $this->assertEquals('7a21db6c19a7d9e1a007af9d2a19f105', $encodeData['sign']);
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

        $yiFuPay = new YiFuPay();
        $yiFuPay->verifyOrderPayment([]);
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

        $yiFuPay = new YiFuPay();
        $yiFuPay->setPrivateKey('test');
        $yiFuPay->setOptions([]);
        $yiFuPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時缺少加密簽名
     */
    public function testReturnWithoutPPostKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        unset($this->returnResult['sign']);

        $yiFuPay = new YiFuPay();
        $yiFuPay->setPrivateKey('test');
        $yiFuPay->setOptions($this->returnResult);
        $yiFuPay->verifyOrderPayment([]);
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

        $this->returnResult['sign'] = 'HEY';

        $yiFuPay = new YiFuPay();
        $yiFuPay->setPrivateKey('test');
        $yiFuPay->setOptions($this->returnResult);
        $yiFuPay->verifyOrderPayment([]);
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

        $this->returnResult['orderstatus'] = 99;
        $this->returnResult['sign'] = 'c3ad4e6d3216d8cb0ed20a671e3cd4e3';

        $yiFuPay = new YiFuPay();
        $yiFuPay->setPrivateKey('test');
        $yiFuPay->setOptions($this->returnResult);
        $yiFuPay->verifyOrderPayment([]);
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

        $entry = ['id' => '201709220000009528'];

        $yiFuPay = new YiFuPay();
        $yiFuPay->setPrivateKey('test');
        $yiFuPay->setOptions($this->returnResult);
        $yiFuPay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回金額不正確的情況
     */
    public function testReturnOrderAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $entry = [
            'id' => '201803212100009527',
            'amount' => '1.25',
        ];

        $yiFuPay = new YiFuPay();
        $yiFuPay->setPrivateKey('test');
        $yiFuPay->setOptions($this->returnResult);
        $yiFuPay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回支付成功
     */
    public function testReturnResultSuccess()
    {
        $entry = [
            'id' => '201803212100009527',
            'amount' => '1.00',
        ];

        $yiFuPay = new YiFuPay();
        $yiFuPay->setPrivateKey('test');
        $yiFuPay->setOptions($this->returnResult);
        $yiFuPay->verifyOrderPayment($entry);

        $this->assertEquals('ok', $yiFuPay->getMsg());
    }
}
