<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\CowBPay;

class CowBPayTest extends DurianTestCase
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
            'number' => '9453',
            'orderId' => '201803070000009453',
            'notify_url' => 'http://www.seafood.help/',
            'amount' => '1.94',
            'paymentVendorId' => '1098',
        ];

        $this->returnResult = [
            'attach' => '',
            'completiontime' => '2018-03-07 10:33:16',
            'msg' => 'SUCCESS',
            'opstate' => '0',
            'orderid' => '201803070000004509',
            'ovalue' => '1.00',
            'sign' => '5edd0965e8d810b506c9f696a85471ff',
            'sysorderid' => '2ec95529826774f22900e1e369a88e03',
            'systime' => '2018-03-07 10:33:16',
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

        $cowBPay = new CowBPay();
        $cowBPay->getVerifyData();
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

        $cowBPay = new CowBPay();
        $cowBPay->setPrivateKey('test');
        $cowBPay->setOptions([]);
        $cowBPay->getVerifyData();
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

        $cowBPay = new CowBPay();
        $cowBPay->setPrivateKey('test');
        $cowBPay->setOptions($this->option);
        $cowBPay->getVerifyData();
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $cowBPay = new CowBPay();
        $cowBPay->setPrivateKey('test');
        $cowBPay->setOptions($this->option);
        $encodeData = $cowBPay->getVerifyData();

        $this->assertEquals('http://www.seafood.help/', $encodeData['callbackurl']);
        $this->assertEquals('201803070000009453', $encodeData['orderid']);
        $this->assertEquals('9453', $encodeData['merchant']);
        $this->assertEquals('1d23bd76e09f1a3576af835e2563093d', $encodeData['sign']);
        $this->assertEquals('220', $encodeData['type']);
        $this->assertEquals('1.94', $encodeData['value']);
        $this->assertEquals('GET', $cowBPay->getPayMethod());
    }

    /**
     * 測試支付寶二維支付
     */
    public function testAliQRcodePay()
    {
        $this->option['paymentVendorId'] = '1092';

        $cowBPay = new CowBPay();
        $cowBPay->setPrivateKey('test');
        $cowBPay->setOptions($this->option);
        $encodeData = $cowBPay->getVerifyData();

        $this->assertEquals('http://www.seafood.help/', $encodeData['callbackurl']);
        $this->assertEquals('201803070000009453', $encodeData['orderid']);
        $this->assertEquals('9453', $encodeData['merchant']);
        $this->assertEquals('00b695f7f7958f62eb07a6aa1bc321d5', $encodeData['sign']);
        $this->assertEquals('210', $encodeData['type']);
        $this->assertEquals('1.94', $encodeData['value']);
        $this->assertEquals('GET', $cowBPay->getPayMethod());
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

        $cowBPay = new CowBPay();
        $cowBPay->verifyOrderPayment([]);
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

        $cowBPay = new CowBPay();
        $cowBPay->setPrivateKey('test');
        $cowBPay->verifyOrderPayment([]);
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

        $cowBPay = new CowBPay();
        $cowBPay->setPrivateKey('test');
        $cowBPay->setOptions($this->returnResult);
        $cowBPay->verifyOrderPayment([]);
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

        $this->returnResult['sign'] = '9453';

        $cowBPay = new CowBPay();
        $cowBPay->setPrivateKey('test');
        $cowBPay->setOptions($this->returnResult);
        $cowBPay->verifyOrderPayment([]);
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

        $this->returnResult['opstate'] = '9453';
        $this->returnResult['sign'] = 'dbc914c879a92d1f4a6bf57647bd8000';

        $cowBPay = new CowBPay();
        $cowBPay->setPrivateKey('test');
        $cowBPay->setOptions($this->returnResult);
        $cowBPay->verifyOrderPayment([]);
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

        $entry = ['id' => '201803070000009487'];

        $cowBPay = new CowBPay();
        $cowBPay->setPrivateKey('test');
        $cowBPay->setOptions($this->returnResult);
        $cowBPay->verifyOrderPayment($entry);
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
            'id' => '201803070000004509',
            'amount' => '999',
        ];

        $cowBPay = new CowBPay();
        $cowBPay->setPrivateKey('test');
        $cowBPay->setOptions($this->returnResult);
        $cowBPay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回時支付成功
     */
    public function testReturnResultSuccess()
    {
        $entry = [
            'id' => '201803070000004509',
            'amount' => '1.00',
        ];

        $cowBPay = new CowBPay();
        $cowBPay->setPrivateKey('test');
        $cowBPay->setOptions($this->returnResult);
        $cowBPay->verifyOrderPayment($entry);

        $this->assertEquals('OK', $cowBPay->getMsg());
    }
}
