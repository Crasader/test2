<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Payment\XinJuYiYunPay;
use BB\DurianBundle\Tests\DurianTestCase;

class XinJuYiYunPayTest extends DurianTestCase
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

        $xinJuYiYunPay = new XinJuYiYunPay();
        $xinJuYiYunPay->getVerifyData();
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

        $xinJuYiYunPay = new XinJuYiYunPay();
        $xinJuYiYunPay->setPrivateKey('test');
        $xinJuYiYunPay->getVerifyData();
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
            'number' => '2376',
            'amount' => '1.00',
            'orderId' => '201802140000009548',
            'paymentVendorId' => '9453',
            'username' => 'php1test',
            'notify_url' => 'http://pay.in-action.tw/',
            'ip' => '192.168.1.1',
            'orderCreateDate' => '2018-02-21 09:27:38',
        ];

        $xinJuYiYunPay = new XinJuYiYunPay();
        $xinJuYiYunPay->setPrivateKey('test');
        $xinJuYiYunPay->setOptions($options);
        $xinJuYiYunPay->getVerifyData();
    }

    /**
     * 測試網銀支付
     */
    public function testBankPay()
    {
        $options = [
            'number' => '2376',
            'amount' => '1.00',
            'orderId' => '201802140000009548',
            'paymentVendorId' => '1',
            'username' => 'php1test',
            'notify_url' => 'http://pay.in-action.tw/',
            'ip' => '192.168.1.1',
            'orderCreateDate' => '2018-02-21 09:27:38',
        ];

        $xinJuYiYunPay = new XinJuYiYunPay();
        $xinJuYiYunPay->setPrivateKey('test');
        $xinJuYiYunPay->setOptions($options);
        $data = $xinJuYiYunPay->getVerifyData();

        $this->assertEquals('MD5', $data['signMethod']);
        $this->assertEquals('YjA3MDMxZjY5YjkwYjlhNGM3MTdjMTRkZTc4Zjk4N2E=', $data['signature']);
        $this->assertEquals('1.0.0', $data['version']);
        $this->assertEquals('php1test', base64_decode($data['subject']));
        $this->assertEquals('', $data['describe']);
        $this->assertEquals('', $data['remark']);
        $this->assertEquals($options['ip'], $data['userIP']);
        $this->assertEquals($options['orderId'], $data['merOrderId']);
        $this->assertEquals('0201', $data['payMode']);
        $this->assertEquals('20180221092738', $data['tradeTime']);
        $this->assertEquals('52', $data['tradeType']);
        $this->assertEquals('01', $data['tradeSubtype']);
        $this->assertEquals('CNY', $data['currency']);
        $this->assertEquals(round($options['amount']) * 100, $data['amount']);
        $this->assertEquals($options['notify_url'], $data['urlBack']);
        $this->assertEquals($options['notify_url'], $data['urlJump']);
        $this->assertEquals($options['number'], $data['merId']);
        $this->assertEquals('', $data['merUserId']);
        $this->assertEquals('100001', $data['bankCode']);
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

        $xinJuYiYunPay = new XinJuYiYunPay();
        $xinJuYiYunPay->verifyOrderPayment([]);
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

        $xinJuYiYunPay = new XinJuYiYunPay();
        $xinJuYiYunPay->setPrivateKey('test');
        $xinJuYiYunPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時未返回簽名
     */
    public function testReturnWithoutSignature()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $options = [
            'signMethod' => 'MD5',
            'merOrderId' => '2018021416383710097100',
            'customerOrderId' => '201802140000009548',
            'notifyType' => '1',
            'notifyTime' => '20180214163931',
            'merId' => '295',
            'remark' => '',
            'amount' => '1100',
            'status' => '0000',
        ];

        $xinJuYiYunPay = new XinJuYiYunPay();
        $xinJuYiYunPay->setPrivateKey('test');
        $xinJuYiYunPay->setOptions($options);
        $xinJuYiYunPay->verifyOrderPayment([]);
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
            'signMethod' => 'MD5',
            'merOrderId' => '2018021416383710097100',
            'customerOrderId' => '201802140000009548',
            'notifyType' => '1',
            'notifyTime' => '20180214163931',
            'merId' => '295',
            'remark' => '',
            'amount' => '1100',
            'status' => '0000',
            'signature' => 'test1234',
        ];

        $xinJuYiYunPay = new XinJuYiYunPay();
        $xinJuYiYunPay->setPrivateKey('test');
        $xinJuYiYunPay->setOptions($options);
        $xinJuYiYunPay->verifyOrderPayment([]);
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

        $options = [
            'signMethod' => 'MD5',
            'merOrderId' => '2018021416383710097100',
            'customerOrderId' => '201802140000009548',
            'notifyType' => '1',
            'notifyTime' => '20180214163931',
            'merId' => '295',
            'remark' => '',
            'amount' => '1100',
            'status' => '1000',
            'signature' => 'N2MzMjYzZTBiY2Y2MzM3NTlhNmZlMzI5NjJmZDI1NTQ=',
        ];

        $xinJuYiYunPay = new XinJuYiYunPay();
        $xinJuYiYunPay->setPrivateKey('test');
        $xinJuYiYunPay->setOptions($options);
        $xinJuYiYunPay->verifyOrderPayment([]);
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
            'signMethod' => 'MD5',
            'merOrderId' => '2018021416383710097100',
            'customerOrderId' => '201802140000009548',
            'notifyType' => '1',
            'notifyTime' => '20180214163931',
            'merId' => '295',
            'remark' => '',
            'amount' => '1100',
            'status' => '0000',
            'signature' => 'MmJiNGUwMzNhZWIxZWI3NjAxMjFlYmRlNmRjNWU3NGI=',
        ];

        $entry = ['id' => '9453'];

        $xinJuYiYunPay = new XinJuYiYunPay();
        $xinJuYiYunPay->setPrivateKey('test');
        $xinJuYiYunPay->setOptions($options);
        $xinJuYiYunPay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果為金額錯誤
     */
    public function testReturnOrderAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $options = [
            'signMethod' => 'MD5',
            'merOrderId' => '2018021416383710097100',
            'customerOrderId' => '201802140000009548',
            'notifyType' => '1',
            'notifyTime' => '20180214163931',
            'merId' => '295',
            'remark' => '',
            'amount' => '1100',
            'status' => '0000',
            'signature' => 'MmJiNGUwMzNhZWIxZWI3NjAxMjFlYmRlNmRjNWU3NGI=',
        ];

        $entry = [
            'id' => '201802140000009548',
            'amount' => '5',
        ];

        $xinJuYiYunPay = new XinJuYiYunPay();
        $xinJuYiYunPay->setPrivateKey('test');
        $xinJuYiYunPay->setOptions($options);
        $xinJuYiYunPay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果
     */
    public function testReturn()
    {
        $options = [
            'signMethod' => 'MD5',
            'merOrderId' => '2018021416383710097100',
            'customerOrderId' => '201802140000009548',
            'notifyType' => '1',
            'notifyTime' => '20180214163931',
            'merId' => '295',
            'remark' => '',
            'amount' => '1100',
            'status' => '0000',
            'signature' => 'MmJiNGUwMzNhZWIxZWI3NjAxMjFlYmRlNmRjNWU3NGI=',
        ];

        $entry = [
            'id' => '201802140000009548',
            'amount' => '11',
        ];

        $xinJuYiYunPay = new XinJuYiYunPay();
        $xinJuYiYunPay->setPrivateKey('test');
        $xinJuYiYunPay->setOptions($options);
        $xinJuYiYunPay->verifyOrderPayment($entry);

        $this->assertEquals('success', $xinJuYiYunPay->getMsg());
    }
}
