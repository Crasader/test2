<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\TelepayIII;
use Buzz\Message\Response;

class TelepayIIITest extends DurianTestCase
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
            'number' => 'CID00801',
            'orderId' => '201805150000005034',
            'notify_url' => 'http://pay.my/pay/pay.php',
            'amount' => '1',
            'paymentVendorId' => '1103',
            'username' => '201805150000005034',
        ];

        $this->returnResult = [
            'scode' => 'CID00801',
            'orderno' => '2018051500000750',
            'orderid' => '201805150000005034',
            'amount' => 1.00,
            'currcode' => 'CNY',
            'memo' => '',
            'resptime' => '2018-05-15 16:38:45',
            'status' => '1',
            'respcode' => 00,
            'paytype' => 'qqpay',
            'productname' => '201805150000005034',
            'sign' => '3f0f8748572930ae93c5c31d9277f7c8',
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

        $telepayIII = new TelepayIII();
        $telepayIII->getVerifyData();
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

        $telepayIII = new TelepayIII();
        $telepayIII->setPrivateKey('test');
        $telepayIII->setOptions([]);
        $telepayIII->getVerifyData();
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

        $telepayIII = new TelepayIII();
        $telepayIII->setPrivateKey('test');
        $telepayIII->setOptions($this->option);
        $telepayIII->getVerifyData();
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $telepayIII = new TelepayIII();
        $telepayIII->setPrivateKey('test');
        $telepayIII->setOptions($this->option);
        $encodeData = $telepayIII->getVerifyData();

        $this->assertEquals('CID00801', $encodeData['scode']);
        $this->assertEquals('201805150000005034', $encodeData['orderid']);
        $this->assertEquals('qqpay', $encodeData['paytype']);
        $this->assertEquals('1.00', $encodeData['amount']);
        $this->assertEquals('201805150000005034', $encodeData['productname']);
        $this->assertEquals('CNY', $encodeData['currcode']);
        $this->assertEquals('201805150000005034', $encodeData['userid']);
        $this->assertEquals('', $encodeData['memo']);
        $this->assertEquals('http://pay.my/pay/pay.php', $encodeData['noticeurl']);
        $this->assertEquals('40ca662910d07987ddcb7cba9634ec3f', $encodeData['sign']);
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

        $telepayIII = new TelepayIII();
        $telepayIII->verifyOrderPayment([]);
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

        $telepayIII = new TelepayIII();
        $telepayIII->setPrivateKey('test');
        $telepayIII->verifyOrderPayment([]);
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

        $telepayIII = new TelepayIII();
        $telepayIII->setPrivateKey('test');
        $telepayIII->setOptions($this->returnResult);
        $telepayIII->verifyOrderPayment([]);
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

        $this->returnResult['sign'] = '8a8dfc0d23d1562976c508ac0e6e00f0';

        $telepayIII = new TelepayIII();
        $telepayIII->setPrivateKey('test');
        $telepayIII->setOptions($this->returnResult);
        $telepayIII->verifyOrderPayment([]);
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

        $this->returnResult['sign'] = '5d37624951ba97e43c1280a153351092';
        $this->returnResult['status'] = '2';

        $telepayIII = new TelepayIII();
        $telepayIII->setPrivateKey('test');
        $telepayIII->setOptions($this->returnResult);
        $telepayIII->verifyOrderPayment([]);
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

        $entry = ['id' => '201805150000005035'];

        $telepayIII = new TelepayIII();
        $telepayIII->setPrivateKey('test');
        $telepayIII->setOptions($this->returnResult);
        $telepayIII->verifyOrderPayment($entry);
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
            'id' => '201805150000005034',
            'amount' => '100',
        ];

        $telepayIII = new TelepayIII();
        $telepayIII->setPrivateKey('test');
        $telepayIII->setOptions($this->returnResult);
        $telepayIII->verifyOrderPayment($entry);
    }

    /**
     * 測試返回時支付成功
     */
    public function testReturnResultSuccess()
    {
        $entry = [
            'id' => '201805150000005034',
            'amount' => '1',
        ];

        $telepayIII = new TelepayIII();
        $telepayIII->setPrivateKey('test');
        $telepayIII->setOptions($this->returnResult);
        $telepayIII->verifyOrderPayment($entry);

        $this->assertEquals('SUCCESS', $telepayIII->getMsg());
    }
}