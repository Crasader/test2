<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\YunTongZhiFu;

class YunTongZhiFuTest extends DurianTestCase
{
    /**
     * 支付時的內部參數
     *
     * @var array
     */
    private $sourceData;

    /**
     * 返回時的參數
     *
     * @var array
     */
    private $returnResult;

    public function setUp()
    {
        parent::setUp();

        $this->sourceData = [
            'number' => '1621',
            'paymentVendorId' => '1',
            'amount' => '1',
            'orderId' => '201805180000012211',
            'notify_url' => 'http://return.php',
        ];

        $this->returnResult = [
            'orderid' => '201805180000012211',
            'opstate' => '0',
            'ovalue' => '1',
            'systime' => '2018/05/18 11:12:01',
            'sysorderid' => '18051811111978020983',
            'completiontime' => '2018/05/18 11:12:01',
            'attach' => '',
            'msg' => '���ɹ�',
            'sign' => '97d7074c9dccd7716aa88e34fc73838b',
        ];
    }

    /**
     * 測試支付加密時沒有帶入privateKey的情況
     */
    public function testPayWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $yunTongZhiFu = new YunTongZhiFu();
        $yunTongZhiFu->getVerifyData();
    }

    /**
     * 測試支付加密時未指定支付參數
     */
    public function testPayWithNoPayParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No pay parameter specified',
            180145
        );

        $yunTongZhiFu = new YunTongZhiFu();
        $yunTongZhiFu->setPrivateKey('1234');
        $yunTongZhiFu->getVerifyData();
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

        $this->sourceData['paymentVendorId'] = '9999';

        $yunTongZhiFu = new YunTongZhiFu();
        $yunTongZhiFu->setPrivateKey('1234');
        $yunTongZhiFu->setOptions($this->sourceData);
        $yunTongZhiFu->getVerifyData();
    }

    /**
     * 測試支付加密
     */
    public function testPay()
    {
        $yunTongZhiFu = new YunTongZhiFu();
        $yunTongZhiFu->setPrivateKey('1234');
        $yunTongZhiFu->setOptions($this->sourceData);
        $encodeData = $yunTongZhiFu->getVerifyData();

        $this->assertEquals('1621', $encodeData['parter']);
        $this->assertEquals('967', $encodeData['type']);
        $this->assertEquals('1.00', $encodeData['value']);
        $this->assertEquals('201805180000012211', $encodeData['orderid']);
        $this->assertEquals('http://return.php', $encodeData['callbackurl']);
        $this->assertEquals('', $encodeData['hrefbackurl']);
        $this->assertEquals('', $encodeData['payerIp']);
        $this->assertEquals('', $encodeData['attach']);
        $this->assertEquals('4852d4a4d05200b14cec75772ea2004d', $encodeData['sign']);
    }

    /**
     * 測試解密基本參數設定沒有帶入key的情況
     */
    public function testReturnWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $yunTongZhiFu = new YunTongZhiFu();
        $yunTongZhiFu->verifyOrderPayment([]);
    }

    /**
     * 測試解密驗證支付平台未指定返回參數
     */
    public function testReturnWithNoReturnParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $yunTongZhiFu = new YunTongZhiFu();
        $yunTongZhiFu->setPrivateKey('1234');
        $yunTongZhiFu->verifyOrderPayment([]);
    }

    /**
     * 測試解密驗證支付平台缺少回傳sign(加密簽名)
     */
    public function testReturnWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        unset($this->returnResult['sign']);

        $yunTongZhiFu = new YunTongZhiFu();
        $yunTongZhiFu->setPrivateKey('1234');
        $yunTongZhiFu->setOptions($this->returnResult);
        $yunTongZhiFu->verifyOrderPayment([]);
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

        $this->returnResult['sign'] = 'error';

        $yunTongZhiFu = new YunTongZhiFu();
        $yunTongZhiFu->setPrivateKey('1234');
        $yunTongZhiFu->setOptions($this->returnResult);
        $yunTongZhiFu->verifyOrderPayment([]);
    }

    /**
     * 測試返回時取得無效的支付參數
     */
    public function testReturnInvalidPayParameters()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Invalid pay parameters',
            180129
        );

        $this->returnResult['opstate'] = '-1';
        $this->returnResult['sign'] = '85dc04a5bdd485756141e866b56abee9';

        $yunTongZhiFu = new YunTongZhiFu();
        $yunTongZhiFu->setPrivateKey('1234');
        $yunTongZhiFu->setOptions($this->returnResult);
        $yunTongZhiFu->verifyOrderPayment([]);
    }

    /**
     * 測試返回結果為支付平台驗證簽名錯誤
     */
    public function testReturnPaymentGatewaySignatureVerificationFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'PaymentGateway error, Merchant sign error',
            180127
        );

        $this->returnResult['opstate'] = '-2';
        $this->returnResult['sign'] = 'b74b92fe5d0f7039abf5d337daac2f69';

        $yunTongZhiFu = new YunTongZhiFu();
        $yunTongZhiFu->setPrivateKey('1234');
        $yunTongZhiFu->setOptions($this->returnResult);
        $yunTongZhiFu->verifyOrderPayment([]);
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

        $this->returnResult['opstate'] = 'other';
        $this->returnResult['sign'] = 'f2019519e67ff0e7c649b73b1e360a13';

        $yunTongZhiFu = new YunTongZhiFu();
        $yunTongZhiFu->setPrivateKey('1234');
        $yunTongZhiFu->setOptions($this->returnResult);
        $yunTongZhiFu->verifyOrderPayment([]);
    }

    /**
     * 測試線上支付單號不正確的情況
     */
    public function testReturnOrderIdError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $entry = ['id' => '201606220000002806'];

        $yunTongZhiFu = new YunTongZhiFu();
        $yunTongZhiFu->setPrivateKey('1234');
        $yunTongZhiFu->setOptions($this->returnResult);
        $yunTongZhiFu->verifyOrderPayment($entry);
    }

    /**
     * 測試線上支付金額不正確的情況
     */
    public function testReturnOrderAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $entry = [
            'id' => '201805180000012211',
            'amount' => '11.0000',
        ];

        $yunTongZhiFu = new YunTongZhiFu();
        $yunTongZhiFu->setPrivateKey('1234');
        $yunTongZhiFu->setOptions($this->returnResult);
        $yunTongZhiFu->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testReturnSuccess()
    {
        $entry = [
            'id' => '201805180000012211',
            'amount' => '1',
        ];

        $yunTongZhiFu = new YunTongZhiFu();
        $yunTongZhiFu->setPrivateKey('1234');
        $yunTongZhiFu->setOptions($this->returnResult);
        $yunTongZhiFu->verifyOrderPayment($entry);

        $this->assertEquals('opstate=0', $yunTongZhiFu->getMsg());
    }
}
