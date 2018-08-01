<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\JinJiePay;

class JinJiePayTest extends DurianTestCase
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

        $jinJiePay = new JinJiePay();
        $jinJiePay->getVerifyData();
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

        $jinJiePay = new JinJiePay();
        $jinJiePay->setPrivateKey('test');
        $jinJiePay->getVerifyData();
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

        $sourceData = [
            'number' => '201705230000',
            'orderId' => '201712270000002219',
            'amount' => '100',
            'notify_url' => 'http://154.58.78.54/',
            'paymentVendorId' => '99',
            'ip' => '127.0.0.1',
            'username' => 'php1test',
        ];

        $jinJiePay = new JinJiePay();
        $jinJiePay->setPrivateKey('test');
        $jinJiePay->setOptions($sourceData);
        $jinJiePay->getVerifyData();
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $sourceData = [
            'number' => '9527',
            'orderId' => '201712270000002219',
            'amount' => '1',
            'notify_url' => 'http://154.58.78.54/',
            'paymentVendorId' => '1097',
            'ip' => '127.0.0.1',
            'username' => 'php1test',
        ];

        $jinJiePay = new JinJiePay();
        $jinJiePay->setPrivateKey('test');
        $jinJiePay->setOptions($sourceData);
        $requestData = $jinJiePay->getVerifyData();

        $this->assertEquals('9527', $requestData['mechno']);
        $this->assertEquals('127.0.0.1', $requestData['orderip']);
        $this->assertEquals('100', $requestData['amount']);
        $this->assertEquals('php1test', $requestData['body']);
        $this->assertEquals('http://154.58.78.54/', $requestData['notifyurl']);
        $this->assertEquals('http://154.58.78.54/', $requestData['returl']);
        $this->assertEquals('201712270000002219', $requestData['orderno']);
        $this->assertEquals('WECHAT', $requestData['payway']);
        $this->assertEquals('WECHAT_H5PAY', $requestData['paytype']);
        $this->assertEquals('04A803937604F55F47FE87B1F66A6C41', $requestData['sign']);
    }

    /**
     * 測試支付時缺少私鑰
     */
    public function testReturnWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $jinJiePay = new JinJiePay();
        $jinJiePay->verifyOrderPayment([]);
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

        $jinJiePay = new JinJiePay();
        $jinJiePay->setPrivateKey('test');
        $jinJiePay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時缺少加密簽名
     */
    public function testReturnWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $sourceData = [
            'status' => '100',
            'charset' => 'utf-8',
            'transactionid' => 'XDDD15143417199211',
            'outtransactionid' => 'null',
            'outorderno' => '201712270000002219',
            'totalfee' => '100',
            'mchid' => '9507',
        ];

        $jinJiePay = new JinJiePay();
        $jinJiePay->setPrivateKey('test');
        $jinJiePay->setOptions($sourceData);
        $jinJiePay->verifyOrderPayment([]);
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

        $sourceData = [
            'status' => '100',
            'charset' => 'utf-8',
            'transactionid' => 'XDDD15143417199211',
            'outtransactionid' => 'null',
            'outorderno' => '201712270000002219',
            'totalfee' => '100',
            'mchid' => '9507',
            'sign' => 'seafood is powerful',
        ];

        $jinJiePay = new JinJiePay();
        $jinJiePay->setPrivateKey('test');
        $jinJiePay->setOptions($sourceData);
        $jinJiePay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時訂單處理中
     */
    public function testReturnOrderProcessing()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Order Processing',
            180059
        );

        $sourceData = [
            'status' => '1',
            'charset' => 'utf-8',
            'transactionid' => 'XDDD15143417199211',
            'outtransactionid' => 'null',
            'outorderno' => '201712270000002219',
            'totalfee' => '100',
            'mchid' => '9507',
            'sign' => 'C84A539E2FC371960EED9843E98A97B2',
        ];

        $jinJiePay = new JinJiePay();
        $jinJiePay->setPrivateKey('test');
        $jinJiePay->setOptions($sourceData);
        $jinJiePay->verifyOrderPayment([]);
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

        $sourceData = [
            'status' => '99',
            'charset' => 'utf-8',
            'transactionid' => 'XDDD15143417199211',
            'outtransactionid' => 'null',
            'outorderno' => '201712270000002219',
            'totalfee' => '100',
            'mchid' => '9507',
            'sign' => 'E0644167359EEBE9CE8CC2621EF6F1CE',
        ];

        $jinJiePay = new JinJiePay();
        $jinJiePay->setPrivateKey('test');
        $jinJiePay->setOptions($sourceData);
        $jinJiePay->verifyOrderPayment([]);
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

        $sourceData = [
            'status' => '100',
            'charset' => 'utf-8',
            'transactionid' => 'XDDD15143417199211',
            'outtransactionid' => 'null',
            'outorderno' => '201712270000002219',
            'totalfee' => '100',
            'mchid' => '9507',
            'sign' => '2387086038A8AF7EBF2D8B14C58EE3DC',
        ];

        $entry = ['id' => '201705220000000321'];

        $jinJiePay = new JinJiePay();
        $jinJiePay->setPrivateKey('test');
        $jinJiePay->setOptions($sourceData);
        $jinJiePay->verifyOrderPayment($entry);
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

        $sourceData = [
            'status' => '100',
            'charset' => 'utf-8',
            'transactionid' => 'XDDD15143417199211',
            'outtransactionid' => 'null',
            'outorderno' => '201712270000002219',
            'totalfee' => '100',
            'mchid' => '9507',
            'sign' => '2387086038A8AF7EBF2D8B14C58EE3DC',
        ];

        $entry = [
            'id' => '201712270000002219',
            'amount' => '10.00',
        ];

        $jinJiePay = new JinJiePay();
        $jinJiePay->setPrivateKey('test');
        $jinJiePay->setOptions($sourceData);
        $jinJiePay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果
     */
    public function testReturnOrder()
    {
        $sourceData = [
            'status' => '100',
            'charset' => 'utf-8',
            'transactionid' => 'XDDD15143417199211',
            'outtransactionid' => 'null',
            'outorderno' => '201712270000002219',
            'totalfee' => '100',
            'mchid' => '9507',
            'sign' => '2387086038A8AF7EBF2D8B14C58EE3DC',
        ];

        $entry = [
            'id' => '201712270000002219',
            'amount' => '1',
        ];

        $jinJiePay = new JinJiePay();
        $jinJiePay->setPrivateKey('test');
        $jinJiePay->setOptions($sourceData);
        $jinJiePay->verifyOrderPayment($entry);

        $this->assertEquals('SUCCESS', $jinJiePay->getMsg());
    }
}
