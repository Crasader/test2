<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\ZhongFuBao;

class ZhongFuBaoTest extends DurianTestCase
{
    /**
     * 測試支付時沒有帶入privateKey的情況
     */
    public function testPayNoPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $zhongFuBao = new ZhongFuBao();
        $zhongFuBao->getVerifyData();
    }

    /**
     * 測試支付時未指定支付參數
     */
    public function testPayNoPayParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No pay parameter specified',
            180145
        );

        $sourceData = ['number' => ''];

        $zhongFuBao = new ZhongFuBao();
        $zhongFuBao->setPrivateKey('1234');
        $zhongFuBao->setOptions($sourceData);
        $zhongFuBao->getVerifyData();
    }

    /**
     * 測試支付時帶入不支援的銀行
     */
    public function testPayNotSupportBank()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'PaymentVendor is not supported by PaymentGateway',
            180066
        );

        $sourceData = [
            'number' => '80087',
            'orderId' => '201710300000007435',
            'amount' => '1',
            'notify_url' => 'http://pay.my/pay/return.php',
            'paymentVendorId' => '999',
        ];

        $zhongFuBao = new ZhongFuBao();
        $zhongFuBao->setPrivateKey('1234');
        $zhongFuBao->setOptions($sourceData);
        $zhongFuBao->getVerifyData();
    }

    /**
     * 測試加密
     */
    public function testPay()
    {
        $sourceData = [
            'number' => '80087',
            'orderId' => '201710300000007435',
            'amount' => '1',
            'notify_url' => 'http://pay.my/pay/return.php',
            'paymentVendorId' => '1',
        ];

        $zhongFuBao = new ZhongFuBao();
        $zhongFuBao->setPrivateKey('1234');
        $zhongFuBao->setOptions($sourceData);
        $encodeData = $zhongFuBao->getVerifyData();

        $this->assertEquals('1.0', $encodeData['version']);
        $this->assertEquals($sourceData['number'], $encodeData['customerid']);
        $this->assertEquals($sourceData['orderId'], $encodeData['sdorderno']);
        $this->assertEquals('1.00', $encodeData['total_fee']);
        $this->assertEquals('bank', $encodeData['paytype']);
        $this->assertEquals('ICBC', $encodeData['bankcode']);
        $this->assertEquals($sourceData['notify_url'], $encodeData['notifyurl']);
        $this->assertEquals($sourceData['notify_url'], $encodeData['returnurl']);
        $this->assertEquals('', $encodeData['remark']);
        $this->assertEquals('d4386bcf33e978c5349c97b5f96a0edb', $encodeData['sign']);
    }

    /**
     * 測試二維加密
     */
    public function testQrcodePay()
    {
        $sourceData = [
            'number' => '80087',
            'orderId' => '201710300000007435',
            'amount' => '1',
            'notify_url' => 'http://pay.my/pay/return.php',
            'paymentVendorId' => '1090',
        ];

        $zhongFuBao = new ZhongFuBao();
        $zhongFuBao->setPrivateKey('1234');
        $zhongFuBao->setOptions($sourceData);
        $encodeData = $zhongFuBao->getVerifyData();

        $this->assertEquals('1.0', $encodeData['version']);
        $this->assertEquals($sourceData['number'], $encodeData['customerid']);
        $this->assertEquals($sourceData['orderId'], $encodeData['sdorderno']);
        $this->assertEquals('1.00', $encodeData['total_fee']);
        $this->assertEquals('weixin', $encodeData['paytype']);
        $this->assertEquals('', $encodeData['bankcode']);
        $this->assertEquals($sourceData['notify_url'], $encodeData['notifyurl']);
        $this->assertEquals($sourceData['notify_url'], $encodeData['returnurl']);
        $this->assertEquals('', $encodeData['remark']);
        $this->assertEquals('d4386bcf33e978c5349c97b5f96a0edb', $encodeData['sign']);
    }

    /**
     * 測試返回時沒有帶入key的情況
     */
    public function testReturnWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $zhongFuBao = new ZhongFuBao();
        $zhongFuBao->verifyOrderPayment([]);
    }

    /**
     * 測試返回時支付平台未指定返回參數
     */
    public function testReturnWithNoReturnParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $sourceData = [];

        $zhongFuBao = new ZhongFuBao();
        $zhongFuBao->setPrivateKey('1234');
        $zhongFuBao->setOptions($sourceData);
        $zhongFuBao->verifyOrderPayment([]);
    }

    /**
     * 測試返回時沒有回傳sign
     */
    public function testReturnWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $sourceData = [
            'status' => '1',
            'customerid' => '10936',
            'sdpayno' => '2017121810595422468',
            'sdorderno' => '201712180000008100',
            'total_fee' => '0.01',
            'paytype' => 'weixin',
            'remark' => '',
        ];

        $zhongFuBao = new ZhongFuBao();
        $zhongFuBao->setPrivateKey('1234');
        $zhongFuBao->setOptions($sourceData);
        $zhongFuBao->verifyOrderPayment([]);
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

        $sourceData = [
            'status' => '1',
            'customerid' => '10936',
            'sdpayno' => '2017121810595422468',
            'sdorderno' => '201712180000008100',
            'total_fee' => '0.01',
            'paytype' => 'weixin',
            'remark' => '',
            'sign' => '99a7040f087f1aa086639411f9e64482',
        ];

        $zhongFuBao = new ZhongFuBao();
        $zhongFuBao->setPrivateKey('1234');
        $zhongFuBao->setOptions($sourceData);
        $zhongFuBao->verifyOrderPayment([]);
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

        $sourceData = [
            'status' => '0',
            'customerid' => '10936',
            'sdpayno' => '2017121810595422468',
            'sdorderno' => '201712180000008100',
            'total_fee' => '0.01',
            'paytype' => 'weixin',
            'remark' => '',
            'sign' => 'bbff64c126c361ce62f3cf51a69286c9',
        ];

        $zhongFuBao = new ZhongFuBao();
        $zhongFuBao->setPrivateKey('1234');
        $zhongFuBao->setOptions($sourceData);
        $zhongFuBao->verifyOrderPayment([]);
    }

    /**
     * 測試返回時單號不正確的情況
     */
    public function testReturnOrderIdError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $sourceData = [
            'status' => '1',
            'customerid' => '10936',
            'sdpayno' => '2017121810595422468',
            'sdorderno' => '201712180000008100',
            'total_fee' => '0.01',
            'paytype' => 'weixin',
            'remark' => '',
            'sign' => 'e2dc0b8a859813d8d001836337276aad',
        ];

        $entry = ['id' => '201405020016748610'];

        $zhongFuBao = new ZhongFuBao();
        $zhongFuBao->setPrivateKey('1234');
        $zhongFuBao->setOptions($sourceData);
        $zhongFuBao->verifyOrderPayment($entry);
    }

    /**
     * 測試返回時金額不正確的情況
     */
    public function testReturnOrderAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $sourceData = [
            'status' => '1',
            'customerid' => '10936',
            'sdpayno' => '2017121810595422468',
            'sdorderno' => '201712180000008100',
            'total_fee' => '0.01',
            'paytype' => 'weixin',
            'remark' => '',
            'sign' => 'e2dc0b8a859813d8d001836337276aad',
        ];

        $entry = [
            'id' => '201712180000008100',
            'amount' => '9900.0000',
        ];

        $zhongFuBao = new ZhongFuBao();
        $zhongFuBao->setPrivateKey('1234');
        $zhongFuBao->setOptions($sourceData);
        $zhongFuBao->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testReturnSuccess()
    {
        $sourceData = [
            'status' => '1',
            'customerid' => '10936',
            'sdpayno' => '2017121810595422468',
            'sdorderno' => '201712180000008100',
            'total_fee' => '0.01',
            'paytype' => 'weixin',
            'remark' => '',
            'sign' => 'e2dc0b8a859813d8d001836337276aad',
        ];

        $entry = [
            'id' => '201712180000008100',
            'amount' => '0.01',
        ];

        $zhongFuBao = new ZhongFuBao();
        $zhongFuBao->setPrivateKey('1234');
        $zhongFuBao->setOptions($sourceData);
        $zhongFuBao->verifyOrderPayment($entry);

        $this->assertEquals('success', $zhongFuBao->getMsg());
    }
}
