<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\XiaoXioBao;

class XiaoXioBaoTest extends DurianTestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->sourceData = [
            'number' => '6001024',
            'amount' => '1',
            'orderId' => '201806130000014282',
            'paymentVendorId' => '1103',
            'orderCreateDate' => '2018-06-13 19:10:11',
            'notify_url' => 'http://fufutest.000webhostapp.com/pay/',
        ];

        $this->returnResult = [
            'orderNo' => '201806207720',
            'merchantOrderNo' => '201806130000014282',
            'money' => '1.00',
            'payAmount' => '0.97',
            'sign' => '9ff63d2ee8d074e999183f3bd871a8cc',
            'payType' => 'QQ',
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

        $xiaoXioBao = new XiaoXioBao();
        $xiaoXioBao->getVerifyData();
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

        $xiaoXioBao = new XiaoXioBao();
        $xiaoXioBao->setPrivateKey('test');
        $xiaoXioBao->getVerifyData();
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

        $xiaoXioBao = new XiaoXioBao();
        $xiaoXioBao->setPrivateKey('test');
        $xiaoXioBao->setOptions($this->sourceData);
        $xiaoXioBao->getVerifyData();
    }

    /**
     * 測試二維支付
     */
    public function testQrcodePay()
    {
        $xiaoXioBao = new XiaoXioBao();
        $xiaoXioBao->setPrivateKey('test');
        $xiaoXioBao->setOptions($this->sourceData);
        $verifyData = $xiaoXioBao->getVerifyData();

        $this->assertEquals('form', $verifyData['type']);
        $this->assertEquals('6001024', $verifyData['merchantId']);
        $this->assertEquals('1', $verifyData['money']);
        $this->assertEquals('1528888211000', $verifyData['timestamp']);
        $this->assertEquals('http://fufutest.000webhostapp.com/pay/', $verifyData['notifyURL']);
        $this->assertEquals('http://fufutest.000webhostapp.com/pay/', $verifyData['returnURL']);
        $this->assertEquals('201806130000014282', $verifyData['merchantOrderId']);
        $this->assertEquals('5fa9e45b390117574a9df9df4aafbecf', $verifyData['sign']);
        $this->assertEquals('QQ', $verifyData['paytype']);
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

        $xiaoXioBao = new XiaoXioBao();
        $xiaoXioBao->verifyOrderPayment([]);
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

        $xiaoXioBao = new XiaoXioBao();
        $xiaoXioBao->setPrivateKey('test');
        $xiaoXioBao->verifyOrderPayment([]);
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

        $xiaoXioBao = new XiaoXioBao();
        $xiaoXioBao->setPrivateKey('test');
        $xiaoXioBao->setOptions($this->returnResult);
        $xiaoXioBao->verifyOrderPayment([]);
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

        $xiaoXioBao = new XiaoXioBao();
        $xiaoXioBao->setPrivateKey('test');
        $xiaoXioBao->setOptions($this->returnResult);
        $xiaoXioBao->verifyOrderPayment([]);
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

        $entry = ['id' => '201503220000000555'];

        $xiaoXioBao = new XiaoXioBao();
        $xiaoXioBao->setPrivateKey('test');
        $xiaoXioBao->setOptions($this->returnResult);
        $xiaoXioBao->verifyOrderPayment($entry);
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
            'id' => '201806130000014282',
            'amount' => '15.00',
        ];

        $xiaoXioBao = new XiaoXioBao();
        $xiaoXioBao->setPrivateKey('test');
        $xiaoXioBao->setOptions($this->returnResult);
        $xiaoXioBao->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果
     */
    public function testReturnSuccess()
    {
        $entry = [
            'id' => '201806130000014282',
            'amount' => '1.00',
        ];

        $xiaoXioBao = new XiaoXioBao();
        $xiaoXioBao->setPrivateKey('test');
        $xiaoXioBao->setOptions($this->returnResult);
        $xiaoXioBao->verifyOrderPayment($entry);

        $this->assertEquals('success', $xiaoXioBao->getMsg());
    }
}
