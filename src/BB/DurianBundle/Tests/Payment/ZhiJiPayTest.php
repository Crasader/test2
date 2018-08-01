<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\ZhiJiPay;

class ZhiJiPayTest extends DurianTestCase
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
            'number' => '180749586',
            'orderId' => '20180711085851981019',
            'notify_url' => 'http://pay.my/pay/pay.php',
            'amount' => '1',
            'paymentVendorId' => '1098',
            'orderCreateDate' => '2018-07-11 08:58:51',
        ];

        $this->returnResult = [
            'memberid' => '180749586',
            'orderid' => '20180711085851981019',
            'transaction_id' => '20180711085851981019',
            'amount' => '1',
            'datetime' => '20180711085851',
            'returncode' => '00',
            'sign' => '63AA3581F3FD534883E9F3582F27B888',
        ];
    }

    /**
     * 測試支付時沒有私鑰
     */
    public function testPayWithoutKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $zhiJiPay = new ZhiJiPay();
        $zhiJiPay->getVerifyData();
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

        $zhiJiPay = new ZhiJiPay();
        $zhiJiPay->setPrivateKey('test');
        $zhiJiPay->setOptions([]);
        $zhiJiPay->getVerifyData();
    }

    /**
     * 測試支付時帶入不支援的銀行
     */
    public function testPayWithUnsupportedPaymentVendor()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'PaymentVendor is not supported by PaymentGateway',
            180066
        );

        $this->option['paymentVendorId'] = '9999';

        $zhiJiPay = new ZhiJiPay();
        $zhiJiPay->setPrivateKey('test');
        $zhiJiPay->setOptions($this->option);
        $zhiJiPay->getVerifyData();
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $zhiJiPay = new ZhiJiPay();
        $zhiJiPay->setPrivateKey('test');
        $zhiJiPay->setOptions($this->option);
        $encodeData = $zhiJiPay->getVerifyData();

        $this->assertEquals('180749586', $encodeData['pay_memberid']);
        $this->assertEquals('20180711085851981019', $encodeData['pay_orderid']);
        $this->assertEquals('2018-07-11 08:58:51', $encodeData['pay_applydate']);
        $this->assertEquals('904', $encodeData['pay_bankcode']);
        $this->assertEquals('http://pay.my/pay/pay.php', $encodeData['pay_notifyurl']);
        $this->assertEquals('http://pay.my/pay/pay.php', $encodeData['pay_callbackurl']);
        $this->assertEquals('1', $encodeData['pay_amount']);
        $this->assertEquals('F1C22F0ACE09D0FD37A2538D996EF5D8', $encodeData['pay_md5sign']);
        $this->assertEquals('20180711085851981019', $encodeData['pay_productname']);
    }

    /**
     * 測試返回時沒有私鑰
     */
    public function testReturnWithoutKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $zhiJiPay = new ZhiJiPay();
        $zhiJiPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時未指定參數
     */
    public function testReturnWithNoParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $zhiJiPay = new ZhiJiPay();
        $zhiJiPay->setPrivateKey('test');
        $zhiJiPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時缺少加密簽名
     */
    public function testReturnWithoutSignature()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        unset($this->returnResult['sign']);

        $zhiJiPay = new ZhiJiPay();
        $zhiJiPay->setPrivateKey('test');
        $zhiJiPay->setOptions($this->returnResult);
        $zhiJiPay->verifyOrderPayment([]);
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

        $this->returnResult['sign'] = 'A4C7BF176B6EF25D1A73671FFDB84AC6';

        $zhiJiPay = new ZhiJiPay();
        $zhiJiPay->setPrivateKey('test');
        $zhiJiPay->setOptions($this->returnResult);
        $zhiJiPay->verifyOrderPayment([]);
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

        $this->returnResult['returncode'] = '99';
        $this->returnResult['sign'] = 'DFAAE8A66349FCFD59A937B6DCB0AA8A';

        $zhiJiPay = new ZhiJiPay();
        $zhiJiPay->setPrivateKey('test');
        $zhiJiPay->setOptions($this->returnResult);
        $zhiJiPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時單號不正確
     */
    public function testReturnPaymentOrderIdError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $entry = ['id' => '20180711085851981018'];

        $zhiJiPay = new ZhiJiPay();
        $zhiJiPay->setPrivateKey('test');
        $zhiJiPay->setOptions($this->returnResult);
        $zhiJiPay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回時金額不正確
     */
    public function testReturnPaymentOrderAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $entry = [
            'id' => '20180711085851981019',
            'amount' => '100',
        ];

        $zhiJiPay = new ZhiJiPay();
        $zhiJiPay->setPrivateKey('test');
        $zhiJiPay->setOptions($this->returnResult);
        $zhiJiPay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回時支付成功
     */
    public function testReturnResultSuccess()
    {
        $entry = [
            'id' => '20180711085851981019',
            'amount' => '1',
        ];

        $zhiJiPay = new ZhiJiPay();
        $zhiJiPay->setPrivateKey('test');
        $zhiJiPay->setOptions($this->returnResult);
        $zhiJiPay->verifyOrderPayment($entry);

        $this->assertEquals('OK', $zhiJiPay->getMsg());
    }
}
