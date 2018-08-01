<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\YingCaiBao;
use Buzz\Message\Response;

class YingCaiBaoTest extends DurianTestCase
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
            'number' => '200002',
            'orderId' => '201802120000007504',
            'notify_url' => 'http://pay.web.my/pay/',
            'amount' => '1',
            'paymentVendorId' => '1090',
        ];

        $this->returnResult = [
            'orderNo' => '20180214200002966535',
            'outId' => '201802120000007504',
            'payMoney' => '1',
            'realPayMoney' => '0.95',
            'remark' => '',
            'payState' => 'success',
            'sign' => '71a56dd2c14776cb293e065f4e38c716',
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

        $yingCaiBao = new YingCaiBao();
        $yingCaiBao->getVerifyData();
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

        $yingCaiBao = new YingCaiBao();
        $yingCaiBao->setPrivateKey('test');
        $yingCaiBao->setOptions([]);
        $yingCaiBao->getVerifyData();
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

        $yingCaiBao = new YingCaiBao();
        $yingCaiBao->setPrivateKey('test');
        $yingCaiBao->setOptions($this->option);
        $yingCaiBao->getVerifyData();
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $yingCaiBao = new YingCaiBao();
        $yingCaiBao->setPrivateKey('2xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx');
        $yingCaiBao->setOptions($this->option);
        $encodeData = $yingCaiBao->getVerifyData();

        $this->assertEquals('200002', $encodeData['partnerId']);
        $this->assertEquals('201802120000007504', $encodeData['outId']);
        $this->assertEquals('wxpay', $encodeData['payType']);
        $this->assertEquals('1', $encodeData['payMoney']);
        $this->assertEquals('', $encodeData['remark']);
        $this->assertEquals('http://pay.web.my/pay/', $encodeData['notifyUrl']);
        $this->assertEquals('http://pay.web.my/pay/', $encodeData['returnUrl']);
        $this->assertEquals('29693c46ca218b4949b9f8073960ebc2', $encodeData['sign']);
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

        $yingCaiBao = new YingCaiBao();
        $yingCaiBao->setPrivateKey('test');
        $yingCaiBao->verifyOrderPayment([]);
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

        $yingCaiBao = new YingCaiBao();
        $yingCaiBao->setPrivateKey('test');
        $yingCaiBao->setOptions($this->returnResult);
        $yingCaiBao->verifyOrderPayment([]);
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

        $yingCaiBao = new YingCaiBao();
        $yingCaiBao->setPrivateKey('test');
        $yingCaiBao->setOptions($this->returnResult);
        $yingCaiBao->verifyOrderPayment([]);
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

        $this->returnResult['payState'] = 'failed';
        $this->returnResult['sign'] = 'f338a2b6ba1669d2958538612fbf1b0a';

        $yingCaiBao = new YingCaiBao();
        $yingCaiBao->setPrivateKey('test');
        $yingCaiBao->setOptions($this->returnResult);
        $yingCaiBao->verifyOrderPayment([]);
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

        $entry = ['id' => '201802210000000000'];

        $yingCaiBao = new YingCaiBao();
        $yingCaiBao->setPrivateKey('2xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx');
        $yingCaiBao->setOptions($this->returnResult);
        $yingCaiBao->verifyOrderPayment($entry);
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
            'id' => '201802120000007504',
            'amount' => '999',
        ];

        $yingCaiBao = new YingCaiBao();
        $yingCaiBao->setPrivateKey('2xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx');
        $yingCaiBao->setOptions($this->returnResult);
        $yingCaiBao->verifyOrderPayment($entry);
    }

    /**
     * 測試返回時支付成功
     */
    public function testReturnResultSuccess()
    {
        $entry = [
            'id' => '201802120000007504',
            'amount' => '1',
        ];

        $yingCaiBao = new YingCaiBao();
        $yingCaiBao->setPrivateKey('2xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx');
        $yingCaiBao->setOptions($this->returnResult);
        $yingCaiBao->verifyOrderPayment($entry);

        $this->assertEquals('success', $yingCaiBao->getMsg());
    }
}
