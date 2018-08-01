<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Payment\KuaiPay;
use BB\DurianBundle\Tests\DurianTestCase;

class KuaiPayTest extends DurianTestCase
{
    /**
     * 提交給支付平台時需要的參數
     *
     * @var array
     */
    private $option;

    /**
     * 支付成功時通知的參數
     *
     * @var array
     */
    private $returnResult;

    public function setUp()
    {
        parent::setUp();

        $this->option = [
            'number' => '9527',
            'orderCreateDate' => '2018-06-15 12:00:16',
            'orderId' => '201806150000046513',
            'amount' => '1',
            'notify_url' => 'http://www.seafood.help/',
            'paymentVendorId' => '1',
        ];

        $this->returnResult = [
            'MemberID' => '9527',
            'TerminalID' => '10066008',
            'TransID' => '201806150000046513',
            'Result' => '1',
            'ResultDesc' => '0001',
            'FactMoney' => '1.00',
            'AdditionalInfo' => '67008180',
            'SuccTime' => '20180615120224',
            'Md5Sign' => '9607c362c32d8437f9b653366ec738d6',
            'BankID' => '',
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

        $kuaiPay = new KuaiPay();
        $kuaiPay->getVerifyData();
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

        $kuaiPay = new KuaiPay();
        $kuaiPay->setPrivateKey('test');
        $kuaiPay->getVerifyData();
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

        $this->option['paymentVendorId'] = '999';

        $kuaiPay = new KuaiPay();
        $kuaiPay->setPrivateKey('test');
        $kuaiPay->setOptions($this->option);
        $kuaiPay->getVerifyData();
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $kuaiPay = new KuaiPay();
        $kuaiPay->setPrivateKey('test');
        $kuaiPay->setOptions($this->option);
        $data = $kuaiPay->getVerifyData();

        $this->assertEquals('9527', $data['MemberID']);
        $this->assertEquals('10066008', $data['TerminalID']);
        $this->assertEquals('4.0', $data['InterfaceVersion']);
        $this->assertEquals('1', $data['KeyType']);
        $this->assertEquals('', $data['PayID']);
        $this->assertEquals('20180615120016', $data['TradeDate']);
        $this->assertEquals('201806150000046513', $data['TransID']);
        $this->assertEquals('1.00', $data['OrderMoney']);
        $this->assertEquals('', $data['ProductName']);
        $this->assertEquals('1', $data['Amount']);
        $this->assertEquals('', $data['Username']);
        $this->assertEquals('', $data['AdditionalInfo']);
        $this->assertEquals('http://www.seafood.help/', $data['PageUrl']);
        $this->assertEquals('http://www.seafood.help/', $data['ReturnUrl']);
        $this->assertEquals('', $data['ResultType']);
        $this->assertEquals('ONLINE_BANK_PAY', $data['PayType']);
        $this->assertEquals('e1e59cbc3b67a324d08ffdba81252b39', $data['Signature']);
        $this->assertEquals('1', $data['NoticeType']);
        $this->assertEquals('ICBC-NET-B2C', $data['bankId']);
        $this->assertEquals('', $data['showPayTypes']);
    }

    /**
     * 測試二維支付
     */
    public function testQrcodePay()
    {
        $this->option['paymentVendorId'] = '1090';

        $kuaiPay = new KuaiPay();
        $kuaiPay->setPrivateKey('test');
        $kuaiPay->setOptions($this->option);
        $data = $kuaiPay->getVerifyData();

        $this->assertEquals('9527', $data['MemberID']);
        $this->assertEquals('10066008', $data['TerminalID']);
        $this->assertEquals('4.0', $data['InterfaceVersion']);
        $this->assertEquals('1', $data['KeyType']);
        $this->assertEquals('', $data['PayID']);
        $this->assertEquals('20180615120016', $data['TradeDate']);
        $this->assertEquals('201806150000046513', $data['TransID']);
        $this->assertEquals('1.00', $data['OrderMoney']);
        $this->assertEquals('', $data['ProductName']);
        $this->assertEquals('1', $data['Amount']);
        $this->assertEquals('', $data['Username']);
        $this->assertEquals('', $data['AdditionalInfo']);
        $this->assertEquals('http://www.seafood.help/', $data['PageUrl']);
        $this->assertEquals('http://www.seafood.help/', $data['ReturnUrl']);
        $this->assertEquals('', $data['ResultType']);
        $this->assertEquals('WECHAT_QRCODE_PAY', $data['PayType']);
        $this->assertEquals('e1e59cbc3b67a324d08ffdba81252b39', $data['Signature']);
        $this->assertEquals('1', $data['NoticeType']);
        $this->assertEquals('', $data['bankId']);
        $this->assertEquals('', $data['showPayTypes']);
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

        $kuaiPay = new KuaiPay();
        $kuaiPay->verifyOrderPayment([]);
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

        $kuaiPay = new KuaiPay();
        $kuaiPay->setPrivateKey('test');
        $kuaiPay->verifyOrderPayment([]);
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

        unset($this->returnResult['Md5Sign']);

        $kuaiPay = new KuaiPay();
        $kuaiPay->setPrivateKey('test');
        $kuaiPay->setOptions($this->returnResult);
        $kuaiPay->verifyOrderPayment([]);
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

        $this->returnResult['Md5Sign'] = '02558e960d0beadbe1fa4a2b11d33853';

        $kuaiPay = new KuaiPay();
        $kuaiPay->setPrivateKey('test');
        $kuaiPay->setOptions($this->returnResult);
        $kuaiPay->verifyOrderPayment([]);
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

        $this->returnResult['Result'] = '0';
        $this->returnResult['Md5Sign'] = 'a41a04bdfcc81cc2c915bcdd36f5ff05';

        $kuaiPay = new KuaiPay();
        $kuaiPay->setPrivateKey('test');
        $kuaiPay->setOptions($this->returnResult);
        $kuaiPay->verifyOrderPayment([]);
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

        $entry = ['id' => '9453'];

        $kuaiPay = new KuaiPay();
        $kuaiPay->setPrivateKey('test');
        $kuaiPay->setOptions($this->returnResult);
        $kuaiPay->verifyOrderPayment($entry);
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

        $entry = [
            'id' => '201806150000046513',
            'amount' => '123',
        ];

        $kuaiPay = new KuaiPay();
        $kuaiPay->setPrivateKey('test');
        $kuaiPay->setOptions($this->returnResult);
        $kuaiPay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果
     */
    public function testReturn()
    {
        $entry = [
            'id' => '201806150000046513',
            'amount' => '1',
        ];

        $kuaiPay = new KuaiPay();
        $kuaiPay->setPrivateKey('test');
        $kuaiPay->setOptions($this->returnResult);
        $kuaiPay->verifyOrderPayment($entry);

        $this->assertEquals('SUCCESS', $kuaiPay->getMsg());
    }
}
