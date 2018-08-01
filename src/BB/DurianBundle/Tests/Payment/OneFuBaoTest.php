<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\OneFuBao;

class OneFuBaoTest extends DurianTestCase
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

        $oneFuBao = new OneFuBao();
        $oneFuBao->getVerifyData();
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

        $oneFuBao = new OneFuBao();
        $oneFuBao->setPrivateKey('test');
        $oneFuBao->getVerifyData();
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
            'number' => '10000024',
            'orderId' => '201711140000007574',
            'amount' => '100',
        ];

        $oneFuBao = new OneFuBao();
        $oneFuBao->setPrivateKey('test');
        $oneFuBao->setOptions($options);
        $oneFuBao->getVerifyData();
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
            'number' => '10000024',
            'orderId' => '201711140000007574',
            'amount' => '100',
            'postUrl' => '',
        ];

        $oneFuBao = new OneFuBao();
        $oneFuBao->setPrivateKey('test');
        $oneFuBao->setOptions($options);
        $oneFuBao->getVerifyData();
    }

    /**
     * 測試網銀支付
     */
    public function testPay()
    {
        $options = [
            'notify_url' => 'http://two123.comxa.com/',
            'paymentVendorId' => '1',
            'number' => '10000024',
            'orderId' => '201711140000007574',
            'amount' => '100',
            'postUrl' => 'http://www.cyqhk.com/payBank.aspx',
        ];

        $oneFuBao = new OneFuBao();
        $oneFuBao->setPrivateKey('test');
        $oneFuBao->setOptions($options);
        $requestData = $oneFuBao->getVerifyData();

        $this->assertEquals($options['number'], $requestData['partner']);
        $this->assertEquals($options['orderId'], $requestData['ordernumber']);
        $this->assertEquals('100.00', $requestData['paymoney']);
        $this->assertEquals($options['notify_url'], $requestData['callbackurl']);
        $this->assertEquals('ICBC', $requestData['banktype']);
        $this->assertEquals('a81ff75201711aafbed1807acc3c66ae', $requestData['sign']);

        $postUr = 'http://www.cyqhk.com/payBank.aspx?partner=10000024&banktype=ICBC&paymoney=100.00' .
            '&ordernumber=201711140000007574&callbackurl=http%3A%2F%2Ftwo123.comxa.com%2F&' .
            'hrefbackurl=&attach=&sign=a81ff75201711aafbed1807acc3c66ae&isshow=1';
        $this->assertEquals($postUr, $requestData['act_url']);
    }

    /**
     * 測試手機支付
     */
    public function testPhonePay()
    {
        $options = [
            'notify_url' => 'http://two123.comxa.com/',
            'paymentVendorId' => '1104',
            'number' => '10000024',
            'orderId' => '201711140000007574',
            'amount' => '100',
            'postUrl' => 'http://www.cyqhk.com/payBank.aspx',
        ];

        $oneFuBao = new OneFuBao();
        $oneFuBao->setPrivateKey('test');
        $oneFuBao->setOptions($options);
        $requestData = $oneFuBao->getVerifyData();

        $this->assertEquals($options['number'], $requestData['partner']);
        $this->assertEquals($options['orderId'], $requestData['ordernumber']);
        $this->assertEquals('100.00', $requestData['paymoney']);
        $this->assertEquals($options['notify_url'], $requestData['callbackurl']);
        $this->assertEquals('TENWAPPAY', $requestData['banktype']);
        $this->assertEquals('ca1ca044b5824a1aa7fac7b91cecc4b8', $requestData['sign']);

        $postUr = 'http://www.cyqhk.com/payBank.aspx?partner=10000024&banktype=TENWAPPAY&paymoney=100.00' .
            '&ordernumber=201711140000007574&callbackurl=http%3A%2F%2Ftwo123.comxa.com%2F&' .
            'hrefbackurl=&attach=&sign=ca1ca044b5824a1aa7fac7b91cecc4b8&isshow=1';
        $this->assertEquals($postUr, $requestData['act_url']);
    }

    /**
     * 測試二維支付
     */
    public function testQRcodePay()
    {
        $options = [
            'notify_url' => 'http://two123.comxa.com/',
            'paymentVendorId' => '1092',
            'number' => '10000024',
            'orderId' => '201711140000007574',
            'amount' => '100',
            'postUrl' => 'http://www.cyqhk.com/payBank.aspx',
        ];

        $oneFuBao = new OneFuBao();
        $oneFuBao->setPrivateKey('test');
        $oneFuBao->setOptions($options);
        $requestData = $oneFuBao->getVerifyData();

        $this->assertEquals($options['number'], $requestData['partner']);
        $this->assertEquals($options['orderId'], $requestData['ordernumber']);
        $this->assertEquals('100.00', $requestData['paymoney']);
        $this->assertEquals($options['notify_url'], $requestData['callbackurl']);
        $this->assertEquals('ALIPAY', $requestData['banktype']);
        $this->assertEquals('04cb0f2479ac370f9cc130a613563225', $requestData['sign']);

        $postUr = 'http://www.cyqhk.com/payBank.aspx?partner=10000024&banktype=ALIPAY&paymoney=100.00' .
            '&ordernumber=201711140000007574&callbackurl=http%3A%2F%2Ftwo123.comxa.com%2F&' .
            'hrefbackurl=&attach=&sign=04cb0f2479ac370f9cc130a613563225&isshow=1';
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

        $oneFuBao = new OneFuBao();
        $oneFuBao->verifyOrderPayment([]);
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

        $oneFuBao = new OneFuBao();
        $oneFuBao->setPrivateKey('test');
        $oneFuBao->verifyOrderPayment([]);
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
            'partner' => '10000024',
            'ordernumber' => '201711140000007574',
            'orderstatus' => '1',
            'paymoney' => '10.0000',
            'sysnumber' => '201711141216187788152462682',
            'attach' => '',
        ];

        $oneFuBao = new OneFuBao();
        $oneFuBao->setPrivateKey('test');
        $oneFuBao->setOptions($options);
        $oneFuBao->verifyOrderPayment([]);
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
            'partner' => '10000024',
            'ordernumber' => '201711140000007574',
            'orderstatus' => '1',
            'paymoney' => '10.0000',
            'sysnumber' => '201711141216187788152462682',
            'attach' => '',
            'sign' => '90e82e21bb0ed5ea9ee5406fdf2e9a37',
        ];

        $oneFuBao = new OneFuBao();
        $oneFuBao->setPrivateKey('test');
        $oneFuBao->setOptions($options);
        $oneFuBao->verifyOrderPayment([]);
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
            'partner' => '10000024',
            'ordernumber' => '201711140000007574',
            'orderstatus' => '9',
            'paymoney' => '10.0000',
            'sysnumber' => '201711141216187788152462682',
            'attach' => '',
            'sign' => '191a9309ad5502ab81f732eef82ffc2d',
        ];
        $oneFuBao = new OneFuBao();
        $oneFuBao->setPrivateKey('test');
        $oneFuBao->setOptions($options);
        $oneFuBao->verifyOrderPayment([]);
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
            'partner' => '10000024',
            'ordernumber' => '201711140000007574',
            'orderstatus' => '1',
            'paymoney' => '10.0000',
            'sysnumber' => '201711141216187788152462682',
            'attach' => '',
            'sign' => '05b65c968eca8d4d9bce4696cd096f09',
        ];

        $entry = ['id' => '201503220000000555'];

        $oneFuBao = new OneFuBao();
        $oneFuBao->setPrivateKey('test');
        $oneFuBao->setOptions($options);
        $oneFuBao->verifyOrderPayment($entry);
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
            'partner' => '10000024',
            'ordernumber' => '201711140000007574',
            'orderstatus' => '1',
            'paymoney' => '10.0000',
            'sysnumber' => '201711141216187788152462682',
            'attach' => '',
            'sign' => '05b65c968eca8d4d9bce4696cd096f09',
        ];

        $entry = [
            'id' => '201711140000007574',
            'amount' => '15.00',
        ];

        $oneFuBao = new OneFuBao();
        $oneFuBao->setPrivateKey('test');
        $oneFuBao->setOptions($options);
        $oneFuBao->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果
     */
    public function testReturnSuccess()
    {
        $options = [
            'partner' => '10000024',
            'ordernumber' => '201711140000007574',
            'orderstatus' => '1',
            'paymoney' => '10.0000',
            'sysnumber' => '201711141216187788152462682',
            'attach' => '',
            'sign' => '05b65c968eca8d4d9bce4696cd096f09',
        ];

        $entry = [
            'id' => '201711140000007574',
            'amount' => '10.00',
        ];

        $oneFuBao = new OneFuBao();
        $oneFuBao->setPrivateKey('test');
        $oneFuBao->setOptions($options);
        $oneFuBao->verifyOrderPayment($entry);

        $this->assertEquals('ok', $oneFuBao->getMsg());
    }
}
