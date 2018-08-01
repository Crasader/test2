<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\ShengXiangPay;

class ShengXiangPayTest extends DurianTestCase
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
            'orderId' => '201804110000045968',
            'amount' => '1.00',
            'notify_url' => 'http://www.seafood.help/',
            'paymentVendorId' => '1',
        ];

        $this->returnResult = [
            'returncode' => '1',
            'userid' => '3411',
            'orderid' => '201804110000045968',
            'money' => '1.00',
            'sign' => 'abf535363371fe7ea44e52aac11542f5',
            'sign2' => '0df7035cea9e15e62617580c8b6a98e5',
            'ext' => '',
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

        $shengXiangPay = new ShengXiangPay();
        $shengXiangPay->getVerifyData();
    }

    /**
     * 測試支付時沒指定支付參數
     */
    public function testPayWithNoPayParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No pay parameter specified',
            180145
        );

        $shengXiangPay = new ShengXiangPay();
        $shengXiangPay->setPrivateKey('test');
        $shengXiangPay->getVerifyData();
    }

    /**
     * 測試支付加密時代入支付平台不支援的銀行
     */
    public function testPayUnsupportedPaymentVendor()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'PaymentVendor is not supported by PaymentGateway',
            180066
        );

        $this->option['paymentVendorId'] = '999';

        $shengXiangPay = new ShengXiangPay();
        $shengXiangPay->setPrivateKey('test');
        $shengXiangPay->setOptions($this->option);
        $shengXiangPay->getVerifyData();
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $shengXiangPay = new ShengXiangPay();
        $shengXiangPay->setPrivateKey('test');
        $shengXiangPay->setOptions($this->option);
        $data = $shengXiangPay->getVerifyData();

        $this->assertEquals('9527', $data['userid']);
        $this->assertEquals('201804110000045968', $data['orderid']);
        $this->assertEquals('1.00', $data['money']);
        $this->assertEquals('http://www.seafood.help/', $data['url']);
        $this->assertEquals('', $data['aurl']);
        $this->assertEquals('1002', $data['bankid']);
        $this->assertEquals('47a16e03f1d2abbf255b3f145940da76', $data['sign']);
        $this->assertEquals('', $data['ext']);
        $this->assertEquals('29bfa6dec1f8e14147405db87bfd0003', $data['sign2']);
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

        $shengXiangPay = new ShengXiangPay();
        $shengXiangPay->verifyOrderPayment([]);
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

        $shengXiangPay = new ShengXiangPay();
        $shengXiangPay->setPrivateKey('test');
        $shengXiangPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時缺少sign
     */
    public function testReturnWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        unset($this->returnResult['sign']);

        $shengXiangPay = new ShengXiangPay();
        $shengXiangPay->setPrivateKey('test');
        $shengXiangPay->setOptions($this->returnResult);
        $shengXiangPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時缺少sign2
     */
    public function testReturnWithoutSign2()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        unset($this->returnResult['sign2']);

        $shengXiangPay = new ShengXiangPay();
        $shengXiangPay->setPrivateKey('test');
        $shengXiangPay->setOptions($this->returnResult);
        $shengXiangPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時sign簽名驗證錯誤
     */
    public function testReturnSignatureVerificationFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Signature verification failed',
            180034
        );

        $this->returnResult['sign'] = 'e0e68494ce8e921762a893a04c47820b';

        $shengXiangPay = new ShengXiangPay();
        $shengXiangPay->setPrivateKey('test');
        $shengXiangPay->setOptions($this->returnResult);
        $shengXiangPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時sign2簽名驗證錯誤
     */
    public function testReturnSignature2VerificationFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Signature verification failed',
            180034
        );

        $this->returnResult['sign2'] = 'e0e68494ce8e921762a893a04c47820b';

        $shengXiangPay = new ShengXiangPay();
        $shengXiangPay->setPrivateKey('test');
        $shengXiangPay->setOptions($this->returnResult);
        $shengXiangPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回結果為支付失敗
     */
    public function testReturnPaymentFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $this->returnResult['returncode'] = '0';
        $this->returnResult['sign'] = '17cf6fb4014a5d7165803d57a69e7b20';
        $this->returnResult['sign2'] = 'ff4cd40cf03fc424136983f541d35dc0';

        $shengXiangPay = new ShengXiangPay();
        $shengXiangPay->setPrivateKey('test');
        $shengXiangPay->setOptions($this->returnResult);
        $shengXiangPay->verifyOrderPayment([]);
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

        $entry = ['id' => '201503220000000555'];

        $shengXiangPay = new ShengXiangPay();
        $shengXiangPay->setPrivateKey('test');
        $shengXiangPay->setOptions($this->returnResult);
        $shengXiangPay->verifyOrderPayment($entry);
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
            'id' => '201804110000045968',
            'amount' => '15.00',
        ];

        $shengXiangPay = new ShengXiangPay();
        $shengXiangPay->setPrivateKey('test');
        $shengXiangPay->setOptions($this->returnResult);
        $shengXiangPay->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testReturnOrder()
    {
        $entry = [
            'id' => '201804110000045968',
            'amount' => '1.00',
        ];

        $shengXiangPay = new ShengXiangPay();
        $shengXiangPay->setPrivateKey('test');
        $shengXiangPay->setOptions($this->returnResult);
        $shengXiangPay->verifyOrderPayment($entry);

        $this->assertEquals('ok', $shengXiangPay->getMsg());
    }
}
