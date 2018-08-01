<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\KuaiRuBao;

class KuaiRuBaoTest extends DurianTestCase
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
            'amount' => '3.69',
            'paymentVendorId' => '1090',
            'notify_url' => 'http://www.seafood.help/',
            'orderId' => '201803212100009527',
        ];

        $this->returnResult = [
            'paysapi_id' => '008379791d4bc5739c641809',
            'orderid' => '201803300000045912',
            'price' => '1.68',
            'realprice' => '1.68',
            'orderuid' => '',
            'key' => 'b3067a0721655b1f233a9f715f76bf65',
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

        $kuaiRuBao = new KuaiRuBao();
        $kuaiRuBao->getVerifyData();
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

        $kuaiRuBao = new KuaiRuBao();
        $kuaiRuBao->setPrivateKey('test');
        $kuaiRuBao->getVerifyData();
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

        $kuaiRuBao = new KuaiRuBao();
        $kuaiRuBao->setPrivateKey('test');
        $kuaiRuBao->setOptions($this->option);
        $kuaiRuBao->getVerifyData();
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $kuaiRuBao = new KuaiRuBao();
        $kuaiRuBao->setPrivateKey('test');
        $kuaiRuBao->setOptions($this->option);
        $data = $kuaiRuBao->getVerifyData();

        $this->assertEquals('9527', $data['uid']);
        $this->assertEquals('3.69', $data['price']);
        $this->assertEquals('2', $data['istype']);
        $this->assertEquals('http://www.seafood.help/', $data['notify_url']);
        $this->assertEquals('http://www.seafood.help/', $data['return_url']);
        $this->assertEquals('201803212100009527', $data['orderid']);
        $this->assertEquals('', $data['orderuid']);
        $this->assertEquals('', $data['goodsname']);
        $this->assertEquals('97aaecbbaa602df307dcbe5df448dc5a', $data['key']);
        $this->assertEquals('2', $data['version']);
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

        $kuaiRuBao = new KuaiRuBao();
        $kuaiRuBao->verifyOrderPayment([]);
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

        $kuaiRuBao = new KuaiRuBao();
        $kuaiRuBao->setPrivateKey('test');
        $kuaiRuBao->verifyOrderPayment([]);
    }

    /**
     * 測試返回時缺少簽名
     */
    public function testReturnWithoutKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        unset($this->returnResult['key']);

        $kuaiRuBao = new KuaiRuBao();
        $kuaiRuBao->setPrivateKey('test');
        $kuaiRuBao->setOptions($this->returnResult);
        $kuaiRuBao->verifyOrderPayment([]);
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

        $this->returnResult['key'] = 'e0e68494ce8e921762a893a04c47820b';

        $kuaiRuBao = new KuaiRuBao();
        $kuaiRuBao->setPrivateKey('test');
        $kuaiRuBao->setOptions($this->returnResult);
        $kuaiRuBao->verifyOrderPayment([]);
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

        $kuaiRuBao = new KuaiRuBao();
        $kuaiRuBao->setPrivateKey('test');
        $kuaiRuBao->setOptions($this->returnResult);
        $kuaiRuBao->verifyOrderPayment($entry);
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
            'id' => '201803300000045912',
            'amount' => '15.00',
        ];

        $kuaiRuBao = new KuaiRuBao();
        $kuaiRuBao->setPrivateKey('test');
        $kuaiRuBao->setOptions($this->returnResult);
        $kuaiRuBao->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testReturnOrder()
    {
        $entry = [
            'id' => '201803300000045912',
            'amount' => '1.68',
        ];

        $kuaiRuBao = new KuaiRuBao();
        $kuaiRuBao->setPrivateKey('test');
        $kuaiRuBao->setOptions($this->returnResult);
        $kuaiRuBao->verifyOrderPayment($entry);

        $this->assertEquals('success', $kuaiRuBao->getMsg());
    }
}
