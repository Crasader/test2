<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\ShouJieZhiFu;

class ShouJieZhiFuTest extends DurianTestCase
{
    /**
     * 訂單參數
     *
     * @var array
     */
    private $options;

    /**
     * 返回結果
     *
     * @var array
     */
    private $returnResult;

    public function setUp()
    {
        parent::setUp();

        $this->options = [
            'number' => '21592',
            'orderId' => '201805150000011549',
            'amount' => '1',
            'paymentVendorId' => '1090',
            'notify_url' => 'http://www.seafood.help/',
        ];

        $this->returnResult = [
            'status' => '1',
            'partner' => '21592',
            'sdpayno' => '2018051519282420645',
            'ordernumber' => '201805150000011549',
            'paymoney' => '1.00',
            'paytype' => 'weixin',
            'remark' => '',
            'sign' => '9e5faa5ec65e6633ed0316e4cd8588d7',
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

        $shouJieZhiFu = new ShouJieZhiFu();
        $shouJieZhiFu->getVerifyData();
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

        $shouJieZhiFu = new ShouJieZhiFu();
        $shouJieZhiFu->setPrivateKey('test');
        $shouJieZhiFu->setOptions([]);
        $shouJieZhiFu->getVerifyData();
    }

    /**
     * 測試支付時代入支付平台不支援的銀行
     */
    public function testPayUnsupportedPaymentVendor()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'PaymentVendor is not supported by PaymentGateway',
            180066
        );

        $this->options['paymentVendorId'] = '9999';

        $shouJieZhiFu = new ShouJieZhiFu();
        $shouJieZhiFu->setPrivateKey('test');
        $shouJieZhiFu->setOptions($this->options);
        $shouJieZhiFu->getVerifyData();
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $shouJieZhiFu = new ShouJieZhiFu();
        $shouJieZhiFu->setPrivateKey('test');
        $shouJieZhiFu->setOptions($this->options);
        $requestData = $shouJieZhiFu->getVerifyData();

        $this->assertEquals('1.0', $requestData['Ver']);
        $this->assertEquals('21592', $requestData['partner']);
        $this->assertEquals('201805150000011549', $requestData['ordernumber']);
        $this->assertEquals('1.00', $requestData['paymoney']);
        $this->assertEquals('weixin', $requestData['paytype']);
        $this->assertEquals('http://www.seafood.help/', $requestData['notifyurl']);
        $this->assertEquals('http://www.seafood.help/', $requestData['returnurl']);
        $this->assertEquals('c81f267ce2e0a083a3a2c710ad0b7a53', $requestData['sign']);
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

        $shouJieZhiFu = new ShouJieZhiFu();
        $shouJieZhiFu->verifyOrderPayment([]);
    }

    /**
     *測試返回時未指定返回參數
     */
    public function testReturnNoReturnParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $shouJieZhiFu = new ShouJieZhiFu();
        $shouJieZhiFu->setPrivateKey('test');
        $shouJieZhiFu->verifyOrderPayment([]);
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

        $shouJieZhiFu = new ShouJieZhiFu();
        $shouJieZhiFu->setPrivateKey('test');
        $shouJieZhiFu->setOptions($this->returnResult);
        $shouJieZhiFu->verifyOrderPayment([]);
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

        $shouJieZhiFu = new ShouJieZhiFu();
        $shouJieZhiFu->setPrivateKey('test');
        $shouJieZhiFu->setOptions($this->returnResult);
        $shouJieZhiFu->verifyOrderPayment([]);
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

        $this->returnResult['status'] = '123';
        $this->returnResult['sign'] = 'defdf175a75adc582e226ac92f11ba7e';

        $shouJieZhiFu = new ShouJieZhiFu();
        $shouJieZhiFu->setPrivateKey('test');
        $shouJieZhiFu->setOptions($this->returnResult);
        $shouJieZhiFu->verifyOrderPayment([]);
    }

    /**
     * 測試返回結果訂單號錯誤
     */
    public function testReturnOrderIdError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $entry = [
            'id' => '201711070000002111',
        ];

        $shouJieZhiFu = new ShouJieZhiFu();
        $shouJieZhiFu->setPrivateKey('test');
        $shouJieZhiFu->setOptions($this->returnResult);
        $shouJieZhiFu->verifyOrderPayment($entry);
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
            'id' => '201805150000011549',
            'amount' => '0.02',
        ];

        $shouJieZhiFu = new ShouJieZhiFu();
        $shouJieZhiFu->setPrivateKey('test');
        $shouJieZhiFu->setOptions($this->returnResult);
        $shouJieZhiFu->verifyOrderPayment($entry);
    }

    /**
     * 測試支付認證成功
     */
    public function testReturn()
    {
        $entry = [
            'id' => '201805150000011549',
            'amount' => '1',
        ];

        $shouJieZhiFu = new ShouJieZhiFu();
        $shouJieZhiFu->setPrivateKey('test');
        $shouJieZhiFu->setOptions($this->returnResult);
        $shouJieZhiFu->verifyOrderPayment($entry);

        $this->assertEquals('success', $shouJieZhiFu->getMsg());
    }
}
