<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\YiZhiFu;
use Buzz\Message\Response;

class YiZhiFuTest extends DurianTestCase
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

        $yiZhiFu = new YiZhiFu();
        $yiZhiFu->getVerifyData();
    }

    /**
     * 測試支付時沒指定支付參數
     */
    public function testPayWithNoPayParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No pay parameter specified',
            180145
        );

        $yiZhiFu = new YiZhiFu();
        $yiZhiFu->setPrivateKey('test');
        $yiZhiFu->getVerifyData();
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
            'notify_url' => 'http://fufutest.000webhostapp.com/pay/',
            'paymentVendorId' => '999',
            'number' => '2008',
            'orderId' => '201805160000013024',
            'amount' => '1.01',
        ];

        $yiZhiFu = new YiZhiFu();
        $yiZhiFu->setPrivateKey('test');
        $yiZhiFu->setOptions($options);
        $yiZhiFu->getVerifyData();
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $options = [
            'notify_url' => 'http://fufutest.000webhostapp.com/pay/',
            'paymentVendorId' => '1103',
            'number' => '2008',
            'orderId' => '201805160000013024',
            'amount' => '1.01',
        ];

        $yiZhiFu = new YiZhiFu();
        $yiZhiFu->setPrivateKey('test');
        $yiZhiFu->setOptions($options);
        $requestData = $yiZhiFu->getVerifyData();

        $this->assertEquals('3.0', $requestData['version']);
        $this->assertEquals('yzfapp.online.interface', $requestData['method']);
        $this->assertEquals('http://fufutest.000webhostapp.com/pay/', $requestData['callbackurl']);
        $this->assertEquals('2008', $requestData['partner']);
        $this->assertEquals('QQ', $requestData['banktype']);
        $this->assertEquals('201805160000013024', $requestData['ordernumber']);
        $this->assertEquals('1.01', $requestData['paymoney']);
        $this->assertEquals('c221a29804be54c3a720d5b4eda48a79', $requestData['sign']);
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

        $yiZhiFu = new YiZhiFu();
        $yiZhiFu->verifyOrderPayment([]);
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

        $yiZhiFu = new YiZhiFu();
        $yiZhiFu->setPrivateKey('test');
        $yiZhiFu->verifyOrderPayment([]);
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

        $options = [
            'partner' => '2008',
            'ordernumber' => '201805160000013024',
            'orderstatus' => '1090',
            'paymoney' => '0.010',
            'sysnumber' => 'ZT170818144153725214',
            'attach' => '',
        ];

        $yiZhiFu = new YiZhiFu();
        $yiZhiFu->setPrivateKey('test');
        $yiZhiFu->setOptions($options);
        $yiZhiFu->verifyOrderPayment([]);
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
            'partner' => '2008',
            'ordernumber' => '201805160000013024',
            'orderstatus' => '1',
            'paymoney' => '0.010',
            'sysnumber' => 'ZT170818144153725214',
            'attach' => '',
            'sign' => '123456789',
        ];

        $yiZhiFu = new YiZhiFu();
        $yiZhiFu->setPrivateKey('test');
        $yiZhiFu->setOptions($options);
        $yiZhiFu->verifyOrderPayment([]);
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
            'partner' => '2008',
            'ordernumber' => '201805160000013024',
            'orderstatus' => '2',
            'paymoney' => '0.010',
            'sysnumber' => 'ZT170818144153725214',
            'attach' => '',
            'sign' => '09c2fcab7eb40e8188da2089fcfc301d',
        ];

        $yiZhiFu = new YiZhiFu();
        $yiZhiFu->setPrivateKey('test');
        $yiZhiFu->setOptions($options);
        $yiZhiFu->verifyOrderPayment([]);
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
            'partner' => '2008',
            'ordernumber' => '201805160000013024',
            'orderstatus' => '1',
            'paymoney' => '0.010',
            'sysnumber' => 'ZT170818144153725214',
            'attach' => '',
            'sign' => 'b37a033353c51ff4f1a9a46e4bf00c1a',
        ];

        $entry = ['id' => '201503220000000555'];

        $yiZhiFu = new YiZhiFu();
        $yiZhiFu->setPrivateKey('test');
        $yiZhiFu->setOptions($options);
        $yiZhiFu->verifyOrderPayment($entry);
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
            'partner' => '2008',
            'ordernumber' => '201805160000013024',
            'orderstatus' => '1',
            'paymoney' => '0.010',
            'sysnumber' => 'ZT170818144153725214',
            'attach' => '',
            'sign' => 'b37a033353c51ff4f1a9a46e4bf00c1a',
        ];

        $entry = [
            'id' => '201805160000013024',
            'amount' => '15.00',
        ];

        $yiZhiFu = new YiZhiFu();
        $yiZhiFu->setPrivateKey('test');
        $yiZhiFu->setOptions($options);
        $yiZhiFu->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testReturnResultSuccess()
    {
        $options = [
            'partner' => '2008',
            'ordernumber' => '201805160000013024',
            'orderstatus' => '1',
            'paymoney' => '0.010',
            'sysnumber' => 'ZT170818144153725214',
            'attach' => '',
            'sign' => 'b37a033353c51ff4f1a9a46e4bf00c1a',
        ];

        $entry = [
            'id' => '201805160000013024',
            'amount' => '0.01',
        ];

        $yiZhiFu = new YiZhiFu();
        $yiZhiFu->setPrivateKey('test');
        $yiZhiFu->setOptions($options);
        $yiZhiFu->verifyOrderPayment($entry);

        $this->assertEquals('ok', $yiZhiFu->getMsg());
    }
}
