<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\ZongXin;

class ZongXinTest extends DurianTestCase
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

        $zongXin = new ZongXin();
        $zongXin->getVerifyData();
    }

    /**
     * 測試支付時沒未指定支付參數
     */
    public function testPayWithNoPayParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No pay parameter specified',
            180145
        );

        $zongXin = new ZongXin();
        $zongXin->setPrivateKey('test');
        $zongXin->getVerifyData();
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
            'notify_url' => 'http://two123.comxa.com/',
            'paymentVendorId' => '100',
            'number' => '16965',
            'orderId' => '201709150000007037',
            'amount' => '100',
        ];

        $zongXin = new ZongXin();
        $zongXin->setPrivateKey('test');
        $zongXin->setOptions($options);
        $zongXin->getVerifyData();
    }

    /**
     * 測試支付沒有帶入postUrl的情況
     */
    public function testPayWithoutPostUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No pay parameter specified',
            180145
        );

        $options = [
            'notify_url' => 'http://two123.comxa.com/',
            'paymentVendorId' => '1092',
            'number' => '16965',
            'orderId' => '201709150000007037',
            'amount' => '100',
            'postUrl' => '',
        ];

        $zongXin = new ZongXin();
        $zongXin->setPrivateKey('test');
        $zongXin->setOptions($options);
        $zongXin->getVerifyData();
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $options = [
            'notify_url' => 'http://two123.comxa.com/',
            'paymentVendorId' => '1092',
            'number' => '16965',
            'orderId' => '201709150000007037',
            'amount' => '100',
            'postUrl' => 'http://www.cyqhk.com/payBank.aspx'
        ];

        $zongXin = new ZongXin();
        $zongXin->setPrivateKey('test');
        $zongXin->setOptions($options);
        $requestData = $zongXin->getVerifyData();

        $this->assertEquals($options['number'], $requestData['partner']);
        $this->assertEquals($options['orderId'], $requestData['ordernumber']);
        $this->assertEquals('100.00', $requestData['paymoney']);
        $this->assertEquals($options['notify_url'], $requestData['callbackurl']);
        $this->assertEquals('ALIPAY', $requestData['banktype']);
        $this->assertEquals('79a9bd1b4428f0a880005c2131cc32d9', $requestData['sign']);

        $postUr = 'http://www.cyqhk.com/payBank.aspx?partner=16965&banktype=ALIPAY&' .
            'paymoney=100.00&ordernumber=201709150000007037&callbackurl=http%3A%2F%2F' .
            'two123.comxa.com%2F&hrefbackurl=&attach=&sign=79a9bd1b4428f0a880005c2131cc32d9';
        $this->assertEquals($postUr, $requestData['act_url']);
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

        $zongXin = new ZongXin();
        $zongXin->verifyOrderPayment([]);
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

        $zongXin = new ZongXin();
        $zongXin->setPrivateKey('test');
        $zongXin->verifyOrderPayment([]);
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
            'partner' => '16965',
            'ordernumber' => '201709150000007037',
            'orderstatus' => '1',
            'paymoney' => '0.0100',
            'sysnumber' => 'ZX170915100977000',
            'attach' => '',
        ];

        $zongXin = new ZongXin();
        $zongXin->setPrivateKey('test');
        $zongXin->setOptions($options);
        $zongXin->verifyOrderPayment([]);
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
            'partner' => '16965',
            'ordernumber' => '201709150000007037',
            'orderstatus' => '1',
            'paymoney' => '0.0100',
            'sysnumber' => 'ZX170915100977000',
            'attach' => '',
            'sign' => '123456789',
        ];

        $zongXin = new ZongXin();
        $zongXin->setPrivateKey('test');
        $zongXin->setOptions($options);
        $zongXin->verifyOrderPayment([]);
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
            'partner' => '16965',
            'ordernumber' => '201709150000007037',
            'orderstatus' => '2',
            'paymoney' => '0.0100',
            'sysnumber' => 'ZX170915100977000',
            'attach' => '',
            'sign' => 'b1d2e5f4d12397f5550162245c724be5',
        ];

        $zongXin = new ZongXin();
        $zongXin->setPrivateKey('test');
        $zongXin->setOptions($options);
        $zongXin->verifyOrderPayment([]);
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
            'partner' => '16965',
            'ordernumber' => '201709150000007037',
            'orderstatus' => '1',
            'paymoney' => '0.0100',
            'sysnumber' => 'ZX170915100977000',
            'attach' => '',
            'sign' => '624fd989ef8c11974cf92a157968ce8a',
        ];

        $entry = ['id' => '201503220000000555'];

        $zongXin = new ZongXin();
        $zongXin->setPrivateKey('test');
        $zongXin->setOptions($options);
        $zongXin->verifyOrderPayment($entry);
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
            'partner' => '16965',
            'ordernumber' => '201709150000007037',
            'orderstatus' => '1',
            'paymoney' => '0.0100',
            'sysnumber' => 'ZX170915100977000',
            'attach' => '',
            'sign' => '624fd989ef8c11974cf92a157968ce8a',
        ];

        $entry = [
            'id' => '201709150000007037',
            'amount' => '15.00',
        ];

        $zongXin = new ZongXin();
        $zongXin->setPrivateKey('test');
        $zongXin->setOptions($options);
        $zongXin->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果
     */
    public function testReturnSuccess()
    {
        $options = [
            'partner' => '16965',
            'ordernumber' => '201709150000007037',
            'orderstatus' => '1',
            'paymoney' => '0.05',
            'sysnumber' => 'ZX170915100977000',
            'attach' => '',
            'sign' => '73fe3c9c46e3e4eb920eefe2be328b4b',
        ];

        $entry = [
            'id' => '201709150000007037',
            'amount' => '0.05',
        ];

        $zongXin = new ZongXin();
        $zongXin->setPrivateKey('test');
        $zongXin->setOptions($options);
        $zongXin->verifyOrderPayment($entry);

        $this->assertEquals('ok', $zongXin->getMsg());
    }
}
