<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\Wpay;

class WpayTest extends DurianTestCase
{
    /**
     * 訂單參數
     *
     * @var array
     */
    private $options;

    /**
     * 返回結果
     *
     * @var array
     */
    private $returnResult;

    public function setUp()
    {
        parent::setUp();

        $this->options = [
            'paymentVendorId' => '1102',
            'orderId' => '201805090000011955',
            'amount' => '1',
            'ip' => '192.168.1.1',
            'orderCreateDate' => '2018-05-09 15:00:06',
            'number' => '24011110',
            'notify_url' => 'http://www.seafood.help/',
        ];

        $this->returnResult = [
            'bank_type' => 'qq',
            'cid' => '24011110',
            'orderid' => '18050914284169e12fb509183e0',
            'out_trade_no' => '201805090000011955',
            'out_transaction_id' => '201805090000011955020180509142930100757143',
            'paytype' => 'bank',
            'result_code' => '0',
            'status' => '0',
            'total_fee' => '100',
            'transaction_id' => '201805091428482711403',
            'sign' => '71810d1e51710f0add6ece8e2188e4c8',

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

        $wpay = new Wpay();
        $wpay->getVerifyData();
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

        $wpay = new Wpay();
        $wpay->setPrivateKey('test');
        $wpay->getVerifyData();
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

        $this->options['paymentVendorId'] = '9999';

        $wpay = new Wpay();
        $wpay->setPrivateKey('test');
        $wpay->setOptions($this->options);
        $wpay->getVerifyData();
    }

    /**
     * 測試二維支付
     */
    public function testQrcodePay()
    {
        $this->options['paymentVendorId'] = '1103';

        $wpay = new Wpay();
        $wpay->setPrivateKey('test');
        $wpay->setOptions($this->options);
        $requestData = $wpay->getVerifyData();

        $this->assertEquals('qq', $requestData['paytype']);
        $this->assertEquals('201805090000011955', $requestData['out_trade_no']);
        $this->assertEquals('201805090000011955', $requestData['body']);
        $this->assertEquals('', $requestData['attach']);
        $this->assertEquals(100, $requestData['total_fee']);
        $this->assertEquals('192.168.1.1', $requestData['create_ip']);
        $this->assertEquals('20180509150006', $requestData['time_start']);
        $this->assertEquals('', $requestData['time_expire']);
        $this->assertEquals('24011110', $requestData['cid']);
        $this->assertEquals('http://www.seafood.help/', $requestData['return_url']);
        $this->assertEquals('http://www.seafood.help/', $requestData['notify_url']);
        $this->assertEquals('a532c46a0e55b5b163510d964201fa89', $requestData['sign']);
        $this->assertEquals('1', $requestData['isfast']);
        $this->assertEquals('0', $requestData['isqrcode']);
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $wpay = new Wpay();
        $wpay->setPrivateKey('test');
        $wpay->setOptions($this->options);
        $requestData = $wpay->getVerifyData();

        $this->assertEquals('bank', $requestData['paytype']);
        $this->assertEquals('201805090000011955', $requestData['out_trade_no']);
        $this->assertEquals('201805090000011955', $requestData['body']);
        $this->assertEquals('YDALL_', $requestData['attach']);
        $this->assertEquals(100, $requestData['total_fee']);
        $this->assertEquals('192.168.1.1', $requestData['create_ip']);
        $this->assertEquals('20180509150006', $requestData['time_start']);
        $this->assertEquals('', $requestData['time_expire']);
        $this->assertEquals('24011110', $requestData['cid']);
        $this->assertEquals('http://www.seafood.help/', $requestData['return_url']);
        $this->assertEquals('http://www.seafood.help/', $requestData['notify_url']);
        $this->assertEquals('735dc7af7ca7de0252a2027eb8c46acc', $requestData['sign']);
        $this->assertEquals('1', $requestData['isfast']);
        $this->assertEquals('0', $requestData['isqrcode']);
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

        $wpay = new Wpay();
        $wpay->verifyOrderPayment([]);
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

        $wpay = new Wpay();
        $wpay->setPrivateKey('test');
        $wpay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時缺少加密簽名
     */
    public function testReturnWithoutSignMsg()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        unset($this->returnResult['sign']);

        $wpay = new Wpay();
        $wpay->setPrivateKey('test');
        $wpay->setOptions($this->returnResult);
        $wpay->verifyOrderPayment([]);
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

        $wpay = new Wpay();
        $wpay->setPrivateKey('test');
        $wpay->setOptions($this->returnResult);
        $wpay->verifyOrderPayment([]);
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

        $this->returnResult['status'] = '123';
        $this->returnResult['sign'] = '24986c201c0754fc3fd3f939e8d513be';

        $wpay = new Wpay();
        $wpay->setPrivateKey('test');
        $wpay->setOptions($this->returnResult);
        $wpay->verifyOrderPayment([]);
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

        $entry = ['id' => '201705220000000321'];

        $wpay = new Wpay();
        $wpay->setPrivateKey('test');
        $wpay->setOptions($this->returnResult);
        $wpay->verifyOrderPayment($entry);
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
            'id' => '201805090000011955',
            'amount' => '11.00',
        ];

        $wpay = new Wpay();
        $wpay->setPrivateKey('test');
        $wpay->setOptions($this->returnResult);
        $wpay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果
     */
    public function testReturnOrder()
    {
        $entry = [
            'id' => '201805090000011955',
            'amount' => '1.0',
        ];

        $wpay = new Wpay();
        $wpay->setPrivateKey('test');
        $wpay->setOptions($this->returnResult);
        $wpay->verifyOrderPayment($entry);

        $this->assertEquals('success', $wpay->getMsg());
    }
}
