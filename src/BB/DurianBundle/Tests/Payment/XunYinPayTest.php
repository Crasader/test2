<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\XunYinPay;

class XunYinPayTest extends DurianTestCase
{
    /**
     * 測試支付加密時沒有帶入privateKey的情況
     */
    public function testPayEncodeWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $xunYinPay = new XunYinPay();
        $xunYinPay->getVerifyData();
    }

    /**
     * 測試支付加密時未指定支付參數
     */
    public function testPayEncodeWithNoPayParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No pay parameter specified',
            180145
        );

        $sourceData = ['number' => ''];

        $xunYinPay = new XunYinPay();
        $xunYinPay->setPrivateKey('1234');
        $xunYinPay->setOptions($sourceData);
        $xunYinPay->getVerifyData();
    }

    /**
     * 測試支付時代入支付平台不支援的銀行
     */
    public function testPayUnsupportedPaymentVendor()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'PaymentVendor is not supported by PaymentGateway',
            180066
        );

        $sourceData = [
            'number' => '1968',
            'paymentVendorId' => '9999',
            'amount' => '0.01',
            'orderId' => '201712070000002945',
            'notify_url' => 'http://pay.my/pay/pay.php',
            'postUrl' => 'http://pay.xunyinpay.cn/bank/index.aspx',
        ];

        $xunYinPay = new XunYinPay();
        $xunYinPay->setPrivateKey('test');
        $xunYinPay->setOptions($sourceData);
        $xunYinPay->getVerifyData();
    }

    /**
     * 測試支付加密時沒有帶入postUrl的情況
     */
    public function testPayEncodeWithoutPostUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No pay parameter specified',
            180145
        );

        $sourceData = [
            'number' => '1968',
            'paymentVendorId' => '1',
            'amount' => '0.01',
            'orderId' => '201712070000002945',
            'notify_url' => 'http://pay.my/pay/pay.php',
            'postUrl' => '',
        ];

        $xunYinPay = new XunYinPay();
        $xunYinPay->setPrivateKey('test');
        $xunYinPay->setOptions($sourceData);
        $xunYinPay->getVerifyData();
    }

    /**
     * 測試支付加密
     */
    public function testPayEncode()
    {
        $sourceData = [
            'number' => '1968',
            'paymentVendorId' => '1',
            'amount' => '0.01',
            'orderId' => '201712070000002945',
            'notify_url' => 'http://pay.my/pay/pay.php',
            'postUrl' => 'http://pay.xunyinpay.cn/bank/index.aspx',
        ];

        $xunYinPay = new XunYinPay();
        $xunYinPay->setPrivateKey('test');
        $xunYinPay->setOptions($sourceData);
        $encodeData = $xunYinPay->getVerifyData();

        $this->assertEquals('1968', $encodeData['parter']);
        $this->assertEquals('967', $encodeData['bank']);
        $this->assertSame('0.01', $encodeData['value']);
        $this->assertEquals('201712070000002945', $encodeData['orderid']);
        $this->assertEquals('http://pay.my/pay/pay.php', $encodeData['callbackurl']);
        $this->assertEquals('3452c89bf14f80d966f1b24ed852005d', $encodeData['sign']);

        // 檢查要提交的網址是否正確
        $data = [];
        $data['parter'] = $encodeData['parter'];
        $data['bank'] = $encodeData['bank'];
        $data['value'] = $encodeData['value'];
        $data['orderid'] = $encodeData['orderid'];
        $data['callbackurl'] = $encodeData['callbackurl'];
        $data['hrefbackurl'] = $encodeData['hrefbackurl'];
        $data['payerIp'] = $encodeData['payerIp'];
        $data['attach'] = $encodeData['attach'];
        $data['sign'] = $encodeData['sign'];

        $this->assertEquals($sourceData['postUrl'] . '?' . http_build_query($data), $encodeData['act_url']);
    }

    /**
     * 測試解密基本參數設定沒有帶入key的情況
     */
    public function testSetDecodeSourceWithoutKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $xunYinPay = new XunYinPay();
        $xunYinPay->verifyOrderPayment([]);
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

        $xunYinPay = new XunYinPay();
        $xunYinPay->setPrivateKey('test');
        $xunYinPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時支付平台缺少回傳sign
     */
    public function testReturnWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $sourceData = [
            'orderid' => '201712070000002945',
            'opstate' => '0',
            'ovalue' => '0.01',
            'systime' => '2017/12/07 15:16:04',
            'sysorderid' => '17120715135558020275',
            'completiontime' => '2017/12/07 15:16:04',
            'attach' => '',
            'msg' => '',
        ];

        $xunYinPay = new XunYinPay();
        $xunYinPay->setPrivateKey('test');
        $xunYinPay->setOptions($sourceData);
        $xunYinPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時sign簽名驗證錯誤
     */
    public function testReturnSignVerificationFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Signature verification failed',
            180034
        );

        $sourceData = [
            'orderid' => '201712070000002945',
            'opstate' => '0',
            'ovalue' => '0.01',
            'systime' => '2017/12/07 15:16:04',
            'sysorderid' => '17120715135558020275',
            'completiontime' => '2017/12/07 15:16:04',
            'attach' => '',
            'msg' => '',
            'sign' => 'ea758a0c27b5cd78c4258433fde82bd9',
        ];

        $xunYinPay = new XunYinPay();
        $xunYinPay->setPrivateKey('test');
        $xunYinPay->setOptions($sourceData);
        $xunYinPay->verifyOrderPayment([]);
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

        $sourceData = [
            'orderid' => '201712070000002945',
            'opstate' => '-1',
            'ovalue' => '0.01',
            'systime' => '2017/12/07 15:16:04',
            'sysorderid' => '17120715135558020275',
            'completiontime' => '2017/12/07 15:16:04',
            'attach' => '',
            'msg' => '',
            'sign' => 'b29c91df20b072e2bcf677aa4627a000',
        ];

        $xunYinPay = new XunYinPay();
        $xunYinPay->setPrivateKey('test');
        $xunYinPay->setOptions($sourceData);
        $xunYinPay->verifyOrderPayment([]);
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

        $sourceData = [
            'orderid' => '201712070000002945',
            'opstate' => '-2',
            'ovalue' => '0.01',
            'systime' => '2017/12/07 15:16:04',
            'sysorderid' => '17120715135558020275',
            'completiontime' => '2017/12/07 15:16:04',
            'attach' => '',
            'msg' => '',
            'sign' => '85104416c6ed55f239ee631c40578f07',
        ];

        $xunYinPay = new XunYinPay();
        $xunYinPay->setPrivateKey('test');
        $xunYinPay->setOptions($sourceData);
        $xunYinPay->verifyOrderPayment([]);
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
            'orderid' => '201712070000002945',
            'opstate' => '-5',
            'ovalue' => '0.01',
            'systime' => '2017/12/07 15:16:04',
            'sysorderid' => '17120715135558020275',
            'completiontime' => '2017/12/07 15:16:04',
            'attach' => '',
            'msg' => '',
            'sign' => '34bcad7af0bedaa7b8edb2328dafbcca',
        ];

        $xunYinPay = new XunYinPay();
        $xunYinPay->setPrivateKey('test');
        $xunYinPay->setOptions($sourceData);
        $xunYinPay->verifyOrderPayment([]);
    }

    /**
     * 測試線上支付單號不正確的情況
     */
    public function testOrderIdError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $sourceData = [
            'orderid' => '201712070000002945',
            'opstate' => '0',
            'ovalue' => '0.01',
            'systime' => '2017/12/07 15:16:04',
            'sysorderid' => '17120715135558020275',
            'completiontime' => '2017/12/07 15:16:04',
            'attach' => '',
            'msg' => '',
            'sign' => '2b18d608785f3c0d168bbf724f4480be',
        ];

        $entry = [
            'id' => '201712070000002946',
            'amount' => '0.01',
        ];

        $xunYinPay = new XunYinPay();
        $xunYinPay->setPrivateKey('test');
        $xunYinPay->setOptions($sourceData);
        $xunYinPay->verifyOrderPayment($entry);
    }

    /**
     * 測試線上支付金額不正確的情況
     */
    public function testOrderAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $sourceData = [
            'orderid' => '201712070000002945',
            'opstate' => '0',
            'ovalue' => '0.01',
            'systime' => '2017/12/07 15:16:04',
            'sysorderid' => '17120715135558020275',
            'completiontime' => '2017/12/07 15:16:04',
            'attach' => '',
            'msg' => '',
            'sign' => '2b18d608785f3c0d168bbf724f4480be',
        ];

        $entry = [
            'id' => '201712070000002945',
            'amount' => '0.02',
        ];

        $xunYinPay = new XunYinPay();
        $xunYinPay->setPrivateKey('test');
        $xunYinPay->setOptions($sourceData);
        $xunYinPay->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testPaySuccess()
    {
        $sourceData = [
            'orderid' => '201712070000002945',
            'opstate' => '0',
            'ovalue' => '0.01',
            'systime' => '2017/12/07 15:16:04',
            'sysorderid' => '17120715135558020275',
            'completiontime' => '2017/12/07 15:16:04',
            'attach' => '',
            'msg' => '',
            'sign' => '2b18d608785f3c0d168bbf724f4480be',
        ];

        $entry = [
            'id' => '201712070000002945',
            'amount' => '0.01',
        ];

        $xunYinPay = new XunYinPay();
        $xunYinPay->setPrivateKey('test');
        $xunYinPay->setOptions($sourceData);
        $xunYinPay->verifyOrderPayment($entry);

        $this->assertEquals('opstate=0', $xunYinPay->getMsg());
    }
}
