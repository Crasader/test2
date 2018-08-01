<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\Lelpay;

class LelpayTest extends DurianTestCase
{
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

        $lelpay = new Lelpay();
        $lelpay->getVerifyData();
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

        $sourceData = ['number' => ''];

        $lelpay = new Lelpay();
        $lelpay->setPrivateKey('1234');
        $lelpay->setOptions($sourceData);
        $lelpay->getVerifyData();
    }

    /**
     * 測試支付加密時帶入支付平台不支援的銀行
     */
    public function testPayUnsupportedPaymentVendor()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'PaymentVendor is not supported by PaymentGateway',
            180066
        );

        $sourceData = [
            'number' => '1286',
            'paymentVendorId' => '999',
            'amount' => '0.01',
            'orderId' => '201709050000006902',
            'notify_url' => 'http://two123.comxa.com/',
            'postUrl' => 'http://124.232.152.238:807/chargebank.aspx',
        ];

        $lelpay = new Lelpay();
        $lelpay->setPrivateKey('1234');
        $lelpay->setOptions($sourceData);
        $lelpay->getVerifyData();
    }

    /**
     * 測試支付加密時沒有帶入postUrl的情況
     */
    public function testPayWithoutPostUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No pay parameter specified',
            180145
        );

        $sourceData = [
            'number' => '1286',
            'paymentVendorId' => '1',
            'amount' => '0.01',
            'orderId' => '201709050000006902',
            'notify_url' => 'http://two123.comxa.com/',
            'postUrl' => '',
        ];

        $lelpay = new Lelpay();
        $lelpay->setPrivateKey('1234');
        $lelpay->setOptions($sourceData);
        $lelpay->getVerifyData();
    }

    /**
     * 測試支付加密
     */
    public function testPay()
    {
        $sourceData = [
            'number' => '1286',
            'paymentVendorId' => '1',
            'amount' => '0.01',
            'orderId' => '201709050000006902',
            'notify_url' => 'http://two123.comxa.com/',
            'postUrl' => 'http://124.232.152.238:807/chargebank.aspx',
        ];

        $lelpay = new Lelpay();
        $lelpay->setPrivateKey('1234');
        $lelpay->setOptions($sourceData);
        $encodeData = $lelpay->getVerifyData();

        $this->assertEquals($sourceData['number'], $encodeData['parter']);
        $this->assertEquals('967', $encodeData['type']);
        $this->assertSame('0.01', $encodeData['value']);
        $this->assertEquals($sourceData['orderId'], $encodeData['orderid']);
        $this->assertEquals($sourceData['notify_url'], $encodeData['callbackurl']);
        $this->assertEquals('42607aa717649bbf04735cecc3306604', $encodeData['sign']);

        // 檢查要提交的網址是否正確
        $actUrl = 'http://124.232.152.238:807/chargebank.aspx?parter=1286&type=967&value=0.01' .
            '&orderid=201709050000006902&callbackurl=http%3A%2F%2Ftwo123.comxa.com%2F&' .
            'hrefbackurl=&payerIp=&attach=&sign=42607aa717649bbf04735cecc3306604';

        $this->assertEquals($actUrl, $encodeData['act_url']);
    }

    /**
     * 測試返回時沒有帶入key的情況
     */
    public function testReturnWithoutKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $lelpay = new Lelpay();
        $lelpay->verifyOrderPayment([]);
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

        $sourceData = [
            'orderid' => '201709050000006902',
            'opstate' => '0',
        ];

        $lelpay = new Lelpay();
        $lelpay->setPrivateKey('1234');
        $lelpay->setOptions($sourceData);
        $lelpay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時支付平台缺少回傳sign(加密簽名)
     */
    public function testReturnWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $sourceData = [
            'orderid' => '201709050000006902',
            'opstate' => '0',
            'ovalue' => '0.01',
        ];

        $lelpay = new Lelpay();
        $lelpay->setPrivateKey('1234');
        $lelpay->setOptions($sourceData);
        $lelpay->verifyOrderPayment([]);
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
            'orderid' => '201709050000006902',
            'opstate' => '0',
            'ovalue' => '0.01',
            'sign' => 'cfe863c91cc417c8a08a43f08b7dfaf8',
            'systime' => '2016-06-22 15:58:08',
            'attach' => '',
        ];

        $lelpay = new Lelpay();
        $lelpay->setPrivateKey('1234');
        $lelpay->setOptions($sourceData);
        $lelpay->verifyOrderPayment([]);
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
            'orderid' => '201709050000006902',
            'opstate' => '-1',
            'ovalue' => '0.01',
            'sign' => 'be68186f390a502cf754d7cad3655c5a',
            'systime' => '2016-06-22 15:58:08',
            'attach' => '',
        ];

        $lelpay = new Lelpay();
        $lelpay->setPrivateKey('1234');
        $lelpay->setOptions($sourceData);
        $lelpay->verifyOrderPayment([]);
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
            'orderid' => '201709050000006902',
            'opstate' => '-2',
            'ovalue' => '0.01',
            'sign' => '36bb6e4c46bd38c740e5d96cfed89e51',
            'systime' => '2016-06-22 15:58:08',
            'attach' => '',
        ];

        $lelpay = new Lelpay();
        $lelpay->setPrivateKey('1234');
        $lelpay->setOptions($sourceData);
        $lelpay->verifyOrderPayment([]);
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
            'orderid' => '201709050000006902',
            'opstate' => '-5',
            'ovalue' => '0.01',
            'sign' => '33465e7af574883669f51af4a7771d72',
            'systime' => '2016-06-22 15:58:08',
            'attach' => '',
        ];

        $lelpay = new Lelpay();
        $lelpay->setPrivateKey('1234');
        $lelpay->setOptions($sourceData);
        $lelpay->verifyOrderPayment([]);
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
            'orderid' => '201709050000006902',
            'opstate' => '0',
            'ovalue' => '0.01',
            'sign' => '5c245c42ffc1fbb4d1557fa5f32c718a',
            'systime' => '2016-06-22 15:58:08',
            'attach' => '',
        ];

        $entry = ['id' => '201606220000002806'];

        $lelpay = new Lelpay();
        $lelpay->setPrivateKey('1234');
        $lelpay->setOptions($sourceData);
        $lelpay->verifyOrderPayment($entry);
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
            'orderid' => '201709050000006902',
            'opstate' => '0',
            'ovalue' => '0.01',
            'sign' => '5c245c42ffc1fbb4d1557fa5f32c718a',
            'systime' => '2016-06-22 15:58:08',
            'attach' => '',
        ];

        $entry = [
            'id' => '201709050000006902',
            'amount' => '1.0000',
        ];

        $lelpay = new Lelpay();
        $lelpay->setPrivateKey('1234');
        $lelpay->setOptions($sourceData);
        $lelpay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回支付驗證成功
     */
    public function testReturnSuccess()
    {
        $sourceData = [
            'orderid' => '201709050000006902',
            'opstate' => '0',
            'ovalue' => '0.01',
            'sign' => '5c245c42ffc1fbb4d1557fa5f32c718a',
            'systime' => '2016-06-22 15:58:08',
            'attach' => '',
        ];

        $entry = [
            'id' => '201709050000006902',
            'amount' => '0.0100',
        ];

        $lelpay = new Lelpay();
        $lelpay->setPrivateKey('1234');
        $lelpay->setOptions($sourceData);
        $lelpay->verifyOrderPayment($entry);

        $this->assertEquals('opstate=0', $lelpay->getMsg());
    }
}
