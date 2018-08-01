<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\HuanYa;

class HuanYaTest extends DurianTestCase
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
            'number' => '10009',
            'orderId' => '201805070000005137',
            'notify_url' => 'http://pay.my/pay/pay.php',
            'amount' => '1',
            'paymentVendorId' => '1103',
            'orderCreateDate' => '2018-05-07 15:14:44',
        ];

        $this->returnResult = [
            'memberid' => '10009',
            'orderid' => '20180507000000513710009',
            'transaction_id' => '20180507151414545454',
            'amount' => '1.00',
            'datetime' => '20180507151444',
            'returncode' => '00',
            'sign' => 'E79E1013006D9A0A645F0221532BF7EA',
            'attach' => '',
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

        $huanYa = new HuanYa();
        $huanYa->getVerifyData();
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

        $huanYa = new HuanYa();
        $huanYa->setPrivateKey('test');
        $huanYa->setOptions([]);
        $huanYa->getVerifyData();
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

        $huanYa = new HuanYa();
        $huanYa->setPrivateKey('test');
        $huanYa->setOptions($this->option);
        $huanYa->getVerifyData();
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $huanYa = new HuanYa();
        $huanYa->setPrivateKey('test');
        $huanYa->setOptions($this->option);
        $encodeData = $huanYa->getVerifyData();

        $this->assertEquals('10009', $encodeData['pay_memberid']);
        $this->assertEquals('20180507000000513710009', $encodeData['pay_orderid']);
        $this->assertEquals('2018-05-07 15:14:44', $encodeData['pay_applydate']);
        $this->assertEquals('908', $encodeData['pay_bankcode']);
        $this->assertEquals('http://pay.my/pay/pay.php', $encodeData['pay_notifyurl']);
        $this->assertEquals('http://pay.my/pay/pay.php', $encodeData['pay_callbackurl']);
        $this->assertEquals('1', $encodeData['pay_amount']);
        $this->assertEquals('3F424C77B0F7EECC0B7D2D49B0FBE5BE', $encodeData['pay_md5sign']);
        $this->assertEquals('201805070000005137', $encodeData['pay_productname']);
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

        $huanYa = new HuanYa();
        $huanYa->verifyOrderPayment([]);
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

        $huanYa = new HuanYa();
        $huanYa->setPrivateKey('test');
        $huanYa->verifyOrderPayment([]);
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

        $huanYa = new HuanYa();
        $huanYa->setPrivateKey('test');
        $huanYa->setOptions($this->returnResult);
        $huanYa->verifyOrderPayment([]);
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

        $huanYa = new HuanYa();
        $huanYa->setPrivateKey('test');
        $huanYa->setOptions($this->returnResult);
        $huanYa->verifyOrderPayment([]);
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
        $this->returnResult['sign'] = 'EEEE5ACB8F82C45FEA7E8F273A78EA19';

        $huanYa = new HuanYa();
        $huanYa->setPrivateKey('test');
        $huanYa->setOptions($this->returnResult);
        $huanYa->verifyOrderPayment([]);
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

        $entry = ['id' => '201805070000005138'];

        $huanYa = new HuanYa();
        $huanYa->setPrivateKey('test');
        $huanYa->setOptions($this->returnResult);
        $huanYa->verifyOrderPayment($entry);
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
            'id' => '201805070000005137',
            'amount' => '100',
        ];

        $huanYa = new HuanYa();
        $huanYa->setPrivateKey('test');
        $huanYa->setOptions($this->returnResult);
        $huanYa->verifyOrderPayment($entry);
    }

    /**
     * 測試返回時支付成功
     */
    public function testReturnResultSuccess()
    {
        $entry = [
            'id' => '201805070000005137',
            'amount' => '1',
        ];

        $huanYa = new HuanYa();
        $huanYa->setPrivateKey('test');
        $huanYa->setOptions($this->returnResult);
        $huanYa->verifyOrderPayment($entry);

        $this->assertEquals('ok', $huanYa->getMsg());
    }
}
