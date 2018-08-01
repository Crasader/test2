<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\HengXinZhiFu;

class HengXinZhiFuTest extends DurianTestCase
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

        $hengXinZhiFu = new HengXinZhiFu();
        $hengXinZhiFu->getVerifyData();
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

        $hengXinZhiFu = new HengXinZhiFu();
        $hengXinZhiFu->setPrivateKey('test');
        $hengXinZhiFu->getVerifyData();
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
            'notify_url' => 'http://orz.com/',
            'paymentVendorId' => '999',
            'number' => '16969',
            'orderId' => '201611110000000104',
            'amount' => '1.01',
        ];

        $hengXinZhiFu = new HengXinZhiFu();
        $hengXinZhiFu->setPrivateKey('test');
        $hengXinZhiFu->setOptions($options);
        $hengXinZhiFu->getVerifyData();
    }

    /**
     * 測試支付設定回傳成功
     */
    public function testPayParameterSuccess()
    {
        $options = [
            'notify_url' => 'http://orz.com/',
            'paymentVendorId' => '1104',
            'number' => '16969',
            'orderId' => '201611110000000104',
            'amount' => '1.01',
        ];

        $hengXinZhiFu = new HengXinZhiFu();
        $hengXinZhiFu->setPrivateKey('test');
        $hengXinZhiFu->setOptions($options);
        $requestData = $hengXinZhiFu->getVerifyData();

        $this->assertEquals('3.0', $requestData['version']);
        $this->assertEquals('hxapp.online.interface', $requestData['method']);
        $this->assertEquals($options['number'], $requestData['partner']);
        $this->assertEquals('QQWAP', $requestData['banktype']);
        $this->assertEquals($options['amount'], $requestData['paymoney']);
        $this->assertEquals($options['orderId'], $requestData['ordernumber']);
        $this->assertEquals($options['notify_url'], $requestData['callbackurl']);
        $this->assertEquals('1', $requestData['isshow']);
        $this->assertEquals('0abb143717b0f7d16f632b7fd5f9902a', $requestData['sign']);
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

        $hengXinZhiFu = new HengXinZhiFu();
        $hengXinZhiFu->verifyOrderPayment([]);
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

        $hengXinZhiFu = new HengXinZhiFu();
        $hengXinZhiFu->setPrivateKey('test');
        $hengXinZhiFu->verifyOrderPayment([]);
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
            'partner' => '16969',
            'ordernumber' => '201611110000000104',
            'orderstatus' => '1090',
            'paymoney' => '0.010',
            'sysnumber' => 'RX1696916111712064553531000',
            'attach' => '',
        ];

        $hengXinZhiFu = new HengXinZhiFu();
        $hengXinZhiFu->setPrivateKey('test');
        $hengXinZhiFu->setOptions($options);
        $hengXinZhiFu->verifyOrderPayment([]);
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
            'partner' => '16969',
            'ordernumber' => '201611110000000104',
            'orderstatus' => '1',
            'paymoney' => '0.010',
            'sysnumber' => 'RX1696916111712064553531000',
            'attach' => '',
            'sign' => '123456789',
        ];

        $hengXinZhiFu = new HengXinZhiFu();
        $hengXinZhiFu->setPrivateKey('test');
        $hengXinZhiFu->setOptions($options);
        $hengXinZhiFu->verifyOrderPayment([]);
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
            'partner' => '16969',
            'ordernumber' => '201611110000000104',
            'orderstatus' => '2',
            'paymoney' => '0.010',
            'sysnumber' => 'RX1696916111712064553531000',
            'attach' => '',
            'sign' => '3c3e8f5efef185efef607b043665fb67',
        ];

        $hengXinZhiFu = new HengXinZhiFu();
        $hengXinZhiFu->setPrivateKey('test');
        $hengXinZhiFu->setOptions($options);
        $hengXinZhiFu->verifyOrderPayment([]);
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
            'partner' => '16969',
            'ordernumber' => '201611110000000104',
            'orderstatus' => '1',
            'paymoney' => '0.010',
            'sysnumber' => 'RX1696916111712064553531000',
            'attach' => '',
            'sign' => 'b16e5a9f1255dc5666fbb5f3612ea141',
        ];

        $entry = ['id' => '201503220000000555'];

        $hengXinZhiFu = new HengXinZhiFu();
        $hengXinZhiFu->setPrivateKey('test');
        $hengXinZhiFu->setOptions($options);
        $hengXinZhiFu->verifyOrderPayment($entry);
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
            'partner' => '16969',
            'ordernumber' => '201611110000000104',
            'orderstatus' => '1',
            'paymoney' => '0.010',
            'sysnumber' => 'RX1696916111712064553531000',
            'attach' => '',
            'sign' => 'b16e5a9f1255dc5666fbb5f3612ea141',
        ];

        $entry = [
            'id' => '201611110000000104',
            'amount' => '15.00',
        ];

        $hengXinZhiFu = new HengXinZhiFu();
        $hengXinZhiFu->setPrivateKey('test');
        $hengXinZhiFu->setOptions($options);
        $hengXinZhiFu->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testReturnOrder()
    {
        $options = [
            'partner' => '16969',
            'ordernumber' => '201611110000000104',
            'orderstatus' => '1',
            'paymoney' => '0.010',
            'sysnumber' => 'RX1696916111712064553531000',
            'attach' => '',
            'sign' => 'b16e5a9f1255dc5666fbb5f3612ea141',
        ];

        $entry = [
            'id' => '201611110000000104',
            'amount' => '0.01',
        ];

        $hengXinZhiFu = new HengXinZhiFu();
        $hengXinZhiFu->setPrivateKey('test');
        $hengXinZhiFu->setOptions($options);
        $hengXinZhiFu->verifyOrderPayment($entry);

        $this->assertEquals('ok', $hengXinZhiFu->getMsg());
    }
}
