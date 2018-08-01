<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\YiYuanBao;

class YiYuanBaoTest extends DurianTestCase
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
            'number' => '100860011',
            'ip' => '127.0.0.1',
            'orderId' => '201805250000005219',
            'orderCreateDate' => '2018-05-25 13:00:00',
            'notify_url' => 'http://pay.my/pay/pay.php',
            'amount' => '1',
            'paymentVendorId' => '1098',
            'merchant_extra' => ['merMark' => 'DP156026'],
        ];

        $this->returnResult = [
            'amount' => '500',
            'nonce_str' => 'NHAl5G40PWgx3ff',
            'merchantNum' => '100860011',
            'sign' => 'B2F08BF331E9A20218BF8D43391949ED',
            'orderNum' => '201805250000005219',
            'orderStatus' => 'SUCCESS',
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

        $yiYuanBao = new YiYuanBao();
        $yiYuanBao->getVerifyData();
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

        $yiYuanBao = new YiYuanBao();
        $yiYuanBao->setPrivateKey('test');
        $yiYuanBao->setOptions([]);
        $yiYuanBao->getVerifyData();
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

        $yiYuanBao = new YiYuanBao();
        $yiYuanBao->setPrivateKey('test');
        $yiYuanBao->setOptions($this->option);
        $yiYuanBao->getVerifyData();
    }

    /**
     * 測試支付缺少商家額外的參數設定merMark
     */
    public function testPayWithoutMerchantExtRamerMark()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No merchant extra value specified',
            180143
        );

        $this->option['merchant_extra'] = [];

        $yiYuanBao = new YiYuanBao();
        $yiYuanBao->setPrivateKey('test');
        $yiYuanBao->setOptions($this->option);
        $yiYuanBao->getVerifyData();
    }

    /**
     * 測試手機支付
     */
    public function testPhonePay()
    {
        $yiYuanBao = new YiYuanBao();
        $yiYuanBao->setPrivateKey('test');
        $yiYuanBao->setOptions($this->option);
        $encodeData = $yiYuanBao->getVerifyData();

        $singData = [];

        $data = ['orderTime', 'signType', 'notifyUrl', 'bank_code', 'sign'];
        foreach ($encodeData as $key => $index) {
            if (!in_array($key, $data)) {
                $singData[$key] = $encodeData[$key];
            }
        }
        $singData['key'] = 'test';
        $signStr = urldecode(http_build_query($singData));

        $this->assertEquals('V1.1', $encodeData['version']);
        $this->assertEquals('100860011', $encodeData['merchantNum']);
        $this->assertEquals('DP156026', $encodeData['merMark']);
        $this->assertEquals('127.0.0.1', $encodeData['client_ip']);
        $this->assertEquals('2018-05-25 13:00:00', $encodeData['orderTime']);
        $this->assertEquals('aliH5', $encodeData['payType']);
        $this->assertEquals('201805250000005219', $encodeData['orderNum']);
        $this->assertEquals('100', $encodeData['amount']);
        $this->assertEquals('201805250000005219', $encodeData['body']);
        $this->assertEquals('MD5', $encodeData['signType']);
        $this->assertEquals('http://pay.my/pay/pay.php', $encodeData['notifyUrl']);
        $this->assertEquals(strtoupper(md5($signStr)), $encodeData['sign']);
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $this->option['paymentVendorId'] = '1';

        $yiYuanBao = new YiYuanBao();
        $yiYuanBao->setPrivateKey('test');
        $yiYuanBao->setOptions($this->option);
        $encodeData = $yiYuanBao->getVerifyData();

        $singData = [];

        $data = ['orderTime', 'signType', 'notifyUrl', 'bank_code', 'sign'];
        foreach ($encodeData as $key => $index) {
            if (!in_array($key, $data)) {
                $singData[$key] = $encodeData[$key];
            }
        }
        $singData['key'] = 'test';
        $signStr = urldecode(http_build_query($singData));

        $this->assertEquals('V1.1', $encodeData['version']);
        $this->assertEquals('100860011', $encodeData['merchantNum']);
        $this->assertEquals('DP156026', $encodeData['merMark']);
        $this->assertEquals('127.0.0.1', $encodeData['client_ip']);
        $this->assertEquals('2018-05-25 13:00:00', $encodeData['orderTime']);
        $this->assertEquals('B2C', $encodeData['payType']);
        $this->assertEquals('201805250000005219', $encodeData['orderNum']);
        $this->assertEquals('100', $encodeData['amount']);
        $this->assertEquals('201805250000005219', $encodeData['body']);
        $this->assertEquals('MD5', $encodeData['signType']);
        $this->assertEquals('http://pay.my/pay/pay.php', $encodeData['notifyUrl']);
        $this->assertEquals(strtoupper(md5($signStr)), $encodeData['sign']);
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

        $yiYuanBao = new YiYuanBao();
        $yiYuanBao->verifyOrderPayment([]);
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

        $yiYuanBao = new YiYuanBao();
        $yiYuanBao->setPrivateKey('test');
        $yiYuanBao->verifyOrderPayment([]);
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

        $yiYuanBao = new YiYuanBao();
        $yiYuanBao->setPrivateKey('test');
        $yiYuanBao->setOptions($this->returnResult);
        $yiYuanBao->verifyOrderPayment([]);
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

        $this->returnResult['sign'] = 'BD09B95D647651F682D7B7C054E433A6';

        $yiYuanBao = new YiYuanBao();
        $yiYuanBao->setPrivateKey('test');
        $yiYuanBao->setOptions($this->returnResult);
        $yiYuanBao->verifyOrderPayment([]);
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

        $this->returnResult['sign'] = '120FB2AE5A41628976F62EED849502ED';
        $this->returnResult['orderStatus'] = 'FAIL';

        $yiYuanBao = new YiYuanBao();
        $yiYuanBao->setPrivateKey('test');
        $yiYuanBao->setOptions($this->returnResult);
        $yiYuanBao->verifyOrderPayment([]);
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

        $entry = ['id' => '201805250000005218'];

        $yiYuanBao = new YiYuanBao();
        $yiYuanBao->setPrivateKey('test');
        $yiYuanBao->setOptions($this->returnResult);
        $yiYuanBao->verifyOrderPayment($entry);
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
            'id' => '201805250000005219',
            'amount' => '500',
        ];

        $yiYuanBao = new YiYuanBao();
        $yiYuanBao->setPrivateKey('test');
        $yiYuanBao->setOptions($this->returnResult);
        $yiYuanBao->verifyOrderPayment($entry);
    }

    /**
     * 測試返回時支付成功
     */
    public function testReturnResultSuccess()
    {
        $entry = [
            'id' => '201805250000005219',
            'amount' => '5',
        ];

        $yiYuanBao = new YiYuanBao();
        $yiYuanBao->setPrivateKey('test');
        $yiYuanBao->setOptions($this->returnResult);
        $yiYuanBao->verifyOrderPayment($entry);

        $this->assertEquals('SUCCESS', $yiYuanBao->getMsg());
    }
}
