<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\ZhaoCaiBao;
use Buzz\Message\Response;

class ZhaoCaiBaoTest extends DurianTestCase
{
    /**
     * @var array
     */
    private $option;

    /**
     * @var array
     */
    private $returnResult;

    public function setUp()
    {
        parent::setUp();

        $this->option = [
            'number' => '200002',
            'orderCreateDate' => '2018-02-21 08:24:19',
            'orderId' => '201802120000007504',
            'notify_url' => 'http://pay.web.my/pay/',
            'amount' => '1',
            'username' => 'php1test',
            'paymentVendorId' => '1090',
            'ip' => '127.0.0.1',
            'verify_url' => 'http://pay.web.my/pay/',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $this->returnResult = [
            'orderNo' => '20180214200002966535',
            'outId' => '201802120000007504',
            'payMoney' => '1',
            'realPayMoney' => '0.95',
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

        $zhaoCaiBao = new ZhaoCaiBao();
        $zhaoCaiBao->getVerifyData();
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

        $zhaoCaiBao = new ZhaoCaiBao();
        $zhaoCaiBao->setPrivateKey('test');
        $zhaoCaiBao->setOptions([]);
        $zhaoCaiBao->getVerifyData();
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

        $zhaoCaiBao = new ZhaoCaiBao();
        $zhaoCaiBao->setPrivateKey('test');
        $zhaoCaiBao->setOptions($this->option);
        $zhaoCaiBao->getVerifyData();
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $zhaoCaiBao = new ZhaoCaiBao();
        $zhaoCaiBao->setPrivateKey('2xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx');
        $zhaoCaiBao->setOptions($this->option);
        $encodeData = $zhaoCaiBao->getVerifyData();

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

        $zhaoCaiBao = new ZhaoCaiBao();
        $zhaoCaiBao->setPrivateKey('test');
        $zhaoCaiBao->verifyOrderPayment([]);
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

        $zhaoCaiBao = new ZhaoCaiBao();
        $zhaoCaiBao->setPrivateKey('test');
        $zhaoCaiBao->setOptions($this->returnResult);
        $zhaoCaiBao->verifyOrderPayment([]);
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

        $zhaoCaiBao = new ZhaoCaiBao();
        $zhaoCaiBao->setPrivateKey('test');
        $zhaoCaiBao->setOptions($this->returnResult);
        $zhaoCaiBao->verifyOrderPayment([]);
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

        $zhaoCaiBao = new ZhaoCaiBao();
        $zhaoCaiBao->setPrivateKey('2xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx');
        $zhaoCaiBao->setOptions($this->returnResult);
        $zhaoCaiBao->verifyOrderPayment($entry);
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

        $zhaoCaiBao = new ZhaoCaiBao();
        $zhaoCaiBao->setPrivateKey('2xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx');
        $zhaoCaiBao->setOptions($this->returnResult);
        $zhaoCaiBao->verifyOrderPayment($entry);
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

        $zhaoCaiBao = new ZhaoCaiBao();
        $zhaoCaiBao->setPrivateKey('2xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx');
        $zhaoCaiBao->setOptions($this->returnResult);
        $zhaoCaiBao->verifyOrderPayment($entry);

        $this->assertEquals('success', $zhaoCaiBao->getMsg());
    }
}
