<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\ShangRuBao;

class ShangRuBaoTest extends DurianTestCase
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

        $ShangRuBao = new ShangRuBao();
        $ShangRuBao->getVerifyData();
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

        $ShangRuBao = new ShangRuBao();
        $ShangRuBao->setPrivateKey('test');
        $ShangRuBao->getVerifyData();
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
            'number' => '9364f8873ae8bd67b85278a5',
            'notify_url' => 'http://pay.my/pay/reutrn.php',
            'paymentVendorId' => '999',
            'orderId' => '201803050000004295',
            'amount' => '0.02',
        ];

        $ShangRuBao = new ShangRuBao();
        $ShangRuBao->setPrivateKey('test');
        $ShangRuBao->setOptions($options);
        $ShangRuBao->getVerifyData();
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $options = [
            'number' => '9364f8873ae8bd67b85278a5',
            'notify_url' => 'http://pay.my/pay/reutrn.php',
            'paymentVendorId' => '1090',
            'orderId' => '201803050000004295',
            'amount' => '0.2',
        ];

        $ShangRuBao = new ShangRuBao();
        $ShangRuBao->setPrivateKey('test');
        $ShangRuBao->setOptions($options);
        $requestData = $ShangRuBao->getVerifyData();

        $this->assertEquals('9364f8873ae8bd67b85278a5', $requestData['uid']);
        $this->assertEquals('0.2', $requestData['price']);
        $this->assertEquals('2', $requestData['istype']);
        $this->assertEquals('http://pay.my/pay/reutrn.php', $requestData['notify_url']);
        $this->assertEquals('http://pay.my/pay/reutrn.php', $requestData['return_url']);
        $this->assertEquals('201803050000004295', $requestData['orderid']);
        $this->assertEquals('', $requestData['orderuid']);
        $this->assertEquals('', $requestData['goodsname']);
        $this->assertEquals('2a859f6870dbbb0c1318e8f68a94c639', $requestData['key']);
        $this->assertEquals('2', $requestData['version']);
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

        $ShangRuBao = new ShangRuBao();
        $ShangRuBao->verifyOrderPayment([]);
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

        $ShangRuBao = new ShangRuBao();
        $ShangRuBao->setPrivateKey('test');
        $ShangRuBao->verifyOrderPayment([]);
    }

    /**
     * 測試返回時缺少簽名
     */
    public function testReturnWithoutSignature()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $options = [
            'paysapi_id' => '9364f8873ae8bd67b85278a5',
            'orderid' => '201803050000004295',
            'price' => '0.2',
            'realprice' => '0.2',
            'orderuid' => '',
        ];

        $ShangRuBao = new ShangRuBao();
        $ShangRuBao->setPrivateKey('test');
        $ShangRuBao->setOptions($options);
        $ShangRuBao->verifyOrderPayment([]);
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
            'paysapi_id' => '9364f8873ae8bd67b85278a5',
            'orderid' => '201803050000004295',
            'price' => '0.2',
            'realprice' => '0.2',
            'orderuid' => '',
            'key' => '8932f2709417c4c7c0340ddde0e953c3',
        ];

        $ShangRuBao = new ShangRuBao();
        $ShangRuBao->setPrivateKey('test');
        $ShangRuBao->setOptions($options);
        $ShangRuBao->verifyOrderPayment([]);
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
            'paysapi_id' => '9364f8873ae8bd67b85278a5',
            'orderid' => '201803050000004295',
            'price' => '0.2',
            'realprice' => '0.2',
            'orderuid' => '',
            'key' => '2348aaec6bbf8bd1e1b7fb48db59d4c9',
        ];

        $entry = ['id' => '201803050000004296'];

        $ShangRuBao = new ShangRuBao();
        $ShangRuBao->setPrivateKey('test');
        $ShangRuBao->setOptions($options);
        $ShangRuBao->verifyOrderPayment($entry);
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
            'paysapi_id' => '9364f8873ae8bd67b85278a5',
            'orderid' => '201803050000004295',
            'price' => '0.2',
            'realprice' => '0.2',
            'orderuid' => '',
            'key' => '2348aaec6bbf8bd1e1b7fb48db59d4c9',
        ];

        $entry = [
            'id' => '201803050000004295',
            'amount' => '2',
        ];

        $ShangRuBao = new ShangRuBao();
        $ShangRuBao->setPrivateKey('test');
        $ShangRuBao->setOptions($options);
        $ShangRuBao->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果
     */
    public function testReturnOrder()
    {
        $options = [
            'paysapi_id' => '9364f8873ae8bd67b85278a5',
            'orderid' => '201803050000004295',
            'price' => '0.2',
            'realprice' => '0.2',
            'orderuid' => '',
            'key' => '2348aaec6bbf8bd1e1b7fb48db59d4c9',
        ];

        $entry = [
            'id' => '201803050000004295',
            'amount' => '0.2',
        ];

        $ShangRuBao = new ShangRuBao();
        $ShangRuBao->setPrivateKey('test');
        $ShangRuBao->setOptions($options);
        $ShangRuBao->verifyOrderPayment($entry);

        $this->assertEquals('success', $ShangRuBao->getMsg());
    }
}
