<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\GeFu;

class GeFuTest extends DurianTestCase
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
            'number' => '5db1fcaec06f43133dfaafd6b3a12797',
            'orderId' => '201805030000005112',
            'notify_url' => 'http://pay.my/pay/pay.php',
            'amount' => '1',
            'paymentVendorId' => '1090',
        ];

        $this->returnResult = [
            'opstate' => '1',
            'orderid' => '201805030000005112',
            'ovalue' => '1',
            'parter' => '5db1fcaec06f43133dfaafd6b3a12797',
            'sign' => '8130795d91c614966f8ea501295aa273',
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

        $gefu = new GeFu();
        $gefu->getVerifyData();
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

        $gefu = new GeFu();
        $gefu->setPrivateKey('test');
        $gefu->setOptions([]);
        $gefu->getVerifyData();
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

        $gefu = new GeFu();
        $gefu->setPrivateKey('test');
        $gefu->setOptions($this->option);
        $gefu->getVerifyData();
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $gefu = new GeFu();
        $gefu->setPrivateKey('test');
        $gefu->setOptions($this->option);
        $encodeData = $gefu->getVerifyData();

        $this->assertEquals('5db1fcaec06f43133dfaafd6b3a12797', $encodeData['parter']);
        $this->assertEquals('1', $encodeData['value']);
        $this->assertEquals('wx', $encodeData['type']);
        $this->assertEquals('201805030000005112', $encodeData['orderid']);
        $this->assertEquals('http://pay.my/pay/pay.php', $encodeData['notifyurl']);
        $this->assertEquals('http://pay.my/pay/pay.php', $encodeData['callbackurl']);
        $this->assertEquals('0d9bbf649ced585f8d55aaa3deb163f6', $encodeData['sign']);
        $this->assertEquals('GET', $gefu->getPayMethod());
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

        $gefu = new GeFu();
        $gefu->verifyOrderPayment([]);
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

        $gefu = new GeFu();
        $gefu->setPrivateKey('test');
        $gefu->verifyOrderPayment([]);
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

        $gefu = new GeFu();
        $gefu->setPrivateKey('test');
        $gefu->setOptions($this->returnResult);
        $gefu->verifyOrderPayment([]);
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

        $this->returnResult['sign'] = 'd07f0ab77e79efc0a73e33a03e7803a2';

        $gefu = new GeFu();
        $gefu->setPrivateKey('test');
        $gefu->setOptions($this->returnResult);
        $gefu->verifyOrderPayment([]);
    }

    /**
     * 測試返回時未支付
     */
    public function testReturnPaymentFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $this->returnResult['opstate'] = '0';
        $this->returnResult['sign'] = 'e1f81bfec174f16001579db7a4818f85';

        $gefu = new GeFu();
        $gefu->setPrivateKey('test');
        $gefu->setOptions($this->returnResult);
        $gefu->verifyOrderPayment([]);
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

        $entry = ['id' => '201805030000005113'];

        $gefu = new GeFu();
        $gefu->setPrivateKey('test');
        $gefu->setOptions($this->returnResult);
        $gefu->verifyOrderPayment($entry);
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
            'id' => '201805030000005112',
            'amount' => '100',
        ];

        $gefu = new GeFu();
        $gefu->setPrivateKey('test');
        $gefu->setOptions($this->returnResult);
        $gefu->verifyOrderPayment($entry);
    }

    /**
     * 測試返回時支付成功
     */
    public function testReturnResultSuccess()
    {
        $entry = [
            'id' => '201805030000005112',
            'amount' => '1',
        ];

        $gefu = new GeFu();
        $gefu->setPrivateKey('test');
        $gefu->setOptions($this->returnResult);
        $gefu->verifyOrderPayment($entry);

        $this->assertEquals('success', $gefu->getMsg());
    }
}
