<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\DuoDuo;

class DuoDuoTest extends DurianTestCase
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

        $duoDuo = new DuoDuo();
        $duoDuo->getVerifyData();
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

        $duoDuo = new DuoDuo();
        $duoDuo->setPrivateKey('test');
        $duoDuo->getVerifyData();
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
            'number' => '90168498',
            'orderId' => '201711300000007841',
            'amount' => '100.00',
            'notify_url' => 'http://twotwo.com/',
            'paymentVendorId' => '99',
            'username' => 'twotwo',
        ];

        $duoDuo = new DuoDuo();
        $duoDuo->setPrivateKey('test');
        $duoDuo->setOptions($sourceData);
        $duoDuo->getVerifyData();
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $sourceData = [
            'number' => '90168498',
            'orderId' => '201711300000007841',
            'amount' => '100.00',
            'notify_url' => 'http://twotwo.com/',
            'paymentVendorId' => '1',
            'username' => 'twotwo',
        ];

        $duoDuo = new DuoDuo();
        $duoDuo->setPrivateKey('test');
        $duoDuo->setOptions($sourceData);
        $requestData = $duoDuo->getVerifyData();

        $this->assertEquals('90168498', $requestData['MerId']);
        $this->assertEquals('201711300000007841', $requestData['OrdId']);
        $this->assertEquals('100.00', $requestData['OrdAmt']);
        $this->assertEquals('DT', $requestData['PayType']);
        $this->assertEquals('CNY', $requestData['CurCode']);
        $this->assertEquals('ICBC', $requestData['BankCode']);
        $this->assertEquals('twotwo', $requestData['ProductInfo']);
        $this->assertEquals('twotwo', $requestData['Remark']);
        $this->assertEquals('http://twotwo.com/', $requestData['ReturnURL']);
        $this->assertEquals('http://twotwo.com/', $requestData['NotifyURL']);
        $this->assertEquals('MD5', $requestData['SignType']);
        $this->assertEquals('16be8e72befa64df2fc50398772b7c80', $requestData['SignInfo']);
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

        $duoDuo = new DuoDuo();
        $duoDuo->verifyOrderPayment([]);
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

        $duoDuo = new DuoDuo();
        $duoDuo->setPrivateKey('test');
        $duoDuo->verifyOrderPayment([]);
    }

    /**
     * 測試返回時缺少加密簽名
     */
    public function testReturnWithoutSignInfo()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $sourceData = [
            'MerId' => '90168498',
            'OrdId' => '201711300000007841',
            'OrdAmt' => '1.00',
            'OrdNo' => 'DTDD2017113015154379192334',
            'ResultCode' => 'success002',
            'Remark' => 'php1test',
            'SignType' => 'MD5',
        ];

        $duoDuo = new DuoDuo();
        $duoDuo->setPrivateKey('test');
        $duoDuo->setOptions($sourceData);
        $duoDuo->verifyOrderPayment([]);
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
            'MerId' => '90168498',
            'OrdId' => '201711300000007841',
            'OrdAmt' => '1.00',
            'OrdNo' => 'DTDD2017113015154379192334',
            'ResultCode' => 'success002',
            'Remark' => 'php1test',
            'SignType' => 'MD5',
            'SignInfo' => 'fe575051cabd13028ac1dc7f1930354b',
        ];

        $duoDuo = new DuoDuo();
        $duoDuo->setPrivateKey('test');
        $duoDuo->setOptions($sourceData);
        $duoDuo->verifyOrderPayment([]);
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
            'MerId' => '90168498',
            'OrdId' => '201711300000007841',
            'OrdAmt' => '1.00',
            'OrdNo' => 'DTDD2017113015154379192334',
            'ResultCode' => 'failed',
            'Remark' => 'php1test',
            'SignType' => 'MD5',
            'SignInfo' => '21d27077a52313e1740c2242b19eb291',
        ];

        $duoDuo = new DuoDuo();
        $duoDuo->setPrivateKey('test');
        $duoDuo->setOptions($sourceData);
        $duoDuo->verifyOrderPayment([]);
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
            'MerId' => '90168498',
            'OrdId' => '201711300000007841',
            'OrdAmt' => '1.00',
            'OrdNo' => 'DTDD2017113015154379192334',
            'ResultCode' => 'success002',
            'Remark' => 'php1test',
            'SignType' => 'MD5',
            'SignInfo' => 'b7d5dd881ced507e41dc5dad20f3adf3',
        ];

        $entry = ['id' => '201705220000000321'];

        $duoDuo = new DuoDuo();
        $duoDuo->setPrivateKey('test');
        $duoDuo->setOptions($sourceData);
        $duoDuo->verifyOrderPayment($entry);
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
            'MerId' => '90168498',
            'OrdId' => '201711300000007841',
            'OrdAmt' => '1.00',
            'OrdNo' => 'DTDD2017113015154379192334',
            'ResultCode' => 'success002',
            'Remark' => 'php1test',
            'SignType' => 'MD5',
            'SignInfo' => 'b7d5dd881ced507e41dc5dad20f3adf3',
        ];

        $entry = [
            'id' => '201711300000007841',
            'amount' => '10.00',
        ];

        $duoDuo = new DuoDuo();
        $duoDuo->setPrivateKey('test');
        $duoDuo->setOptions($sourceData);
        $duoDuo->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果
     */
    public function testReturnOrder()
    {
        $sourceData = [
            'MerId' => '90168498',
            'OrdId' => '201711300000007841',
            'OrdAmt' => '1.00',
            'OrdNo' => 'DTDD2017113015154379192334',
            'ResultCode' => 'success002',
            'Remark' => 'php1test',
            'SignType' => 'MD5',
            'SignInfo' => 'b7d5dd881ced507e41dc5dad20f3adf3',
        ];

        $entry = [
            'id' => '201711300000007841',
            'amount' => '1.00',
        ];

        $duoDuo = new DuoDuo();
        $duoDuo->setPrivateKey('test');
        $duoDuo->setOptions($sourceData);
        $duoDuo->verifyOrderPayment($entry);

        $this->assertEquals('success|9999', $duoDuo->getMsg());
    }
}
