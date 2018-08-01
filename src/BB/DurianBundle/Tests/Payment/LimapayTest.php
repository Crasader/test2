<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\Limapay;

class LimapayTest extends DurianTestCase
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

        $limapay = new Limapay();
        $limapay->getVerifyData();
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

        $limapay = new Limapay();
        $limapay->setPrivateKey('1234');
        $limapay->setOptions($sourceData);
        $limapay->getVerifyData();
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
            'number' => '10035',
            'paymentVendorId' => '999',
            'amount' => '0.01',
            'orderId' => '201710200000007313',
            'notify_url' => 'http://two123.comxa.com/',
            'postUrl' => 'http://www.limapay.net/interface/chargebank.aspx',
        ];

        $limapay = new Limapay();
        $limapay->setPrivateKey('1234');
        $limapay->setOptions($sourceData);
        $limapay->getVerifyData();
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
            'number' => '10035',
            'paymentVendorId' => '1',
            'amount' => '0.01',
            'orderId' => '201710200000007313',
            'notify_url' => 'http://two123.comxa.com/',
            'postUrl' => '',
        ];

        $limapay = new Limapay();
        $limapay->setPrivateKey('1234');
        $limapay->setOptions($sourceData);
        $limapay->getVerifyData();
    }

    /**
     * 測試支付加密
     */
    public function testPay()
    {
        $sourceData = [
            'number' => '10035',
            'paymentVendorId' => '1',
            'amount' => '0.01',
            'orderId' => '201710200000007313',
            'notify_url' => 'http://two123.comxa.com/',
            'postUrl' => 'http://www.limapay.net/interface/chargebank.aspx',
        ];

        $limapay = new Limapay();
        $limapay->setPrivateKey('1234');
        $limapay->setOptions($sourceData);
        $encodeData = $limapay->getVerifyData();

        $this->assertEquals($sourceData['number'], $encodeData['parter']);
        $this->assertEquals('967', $encodeData['type']);
        $this->assertSame('0.01', $encodeData['value']);
        $this->assertEquals($sourceData['orderId'], $encodeData['orderid']);
        $this->assertEquals($sourceData['notify_url'], $encodeData['callbackurl']);
        $this->assertEquals('154a60ee179cda9ae3c25371d1691e41', $encodeData['sign']);

        // 檢查要提交的網址是否正確
        $actUrl = 'http://www.limapay.net/interface/chargebank.aspx?type=967&parter=10035&value=0.01' .
            '&orderid=201710200000007313&callbackurl=http%3A%2F%2Ftwo123.comxa.com%2F&hrefbackurl=http' .
            '%3A%2F%2Ftwo123.comxa.com%2F&attach=&sign=154a60ee179cda9ae3c25371d1691e41';
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

        $limapay = new Limapay();
        $limapay->verifyOrderPayment([]);
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
            'orderid' => '201710200000007313',
            'restate' => '0',
        ];

        $limapay = new Limapay();
        $limapay->setPrivateKey('1234');
        $limapay->setOptions($sourceData);
        $limapay->verifyOrderPayment([]);
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
            'orderid' => '201710200000007313',
            'restate' => '0',
            'ovalue' => '0.01',
        ];

        $limapay = new Limapay();
        $limapay->setPrivateKey('1234');
        $limapay->setOptions($sourceData);
        $limapay->verifyOrderPayment([]);
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
            'parter' => '1035',
            'orderid' => '201710200000007313',
            'restate' => '0',
            'ovalue' => '0.01',
            'sign' => '48e44438415a60295b946467282d6c93',
            'attach' => '',
        ];

        $limapay = new Limapay();
        $limapay->setPrivateKey('1234');
        $limapay->setOptions($sourceData);
        $limapay->verifyOrderPayment([]);
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
            'parter' => '1035',
            'orderid' => '201710200000007313',
            'restate' => '-1',
            'ovalue' => '0.01',
            'sign' => '48e44438415a60295b946467282d6c93',
            'attach' => '',
        ];

        $limapay = new Limapay();
        $limapay->setPrivateKey('1234');
        $limapay->setOptions($sourceData);
        $limapay->verifyOrderPayment([]);
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
            'parter' => '1035',
            'orderid' => '201710200000007313',
            'restate' => '-2',
            'ovalue' => '0.01',
            'sign' => '44736c2634aae07724e789675b9239c9',
            'attach' => '',
        ];

        $limapay = new Limapay();
        $limapay->setPrivateKey('1234');
        $limapay->setOptions($sourceData);
        $limapay->verifyOrderPayment([]);
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
            'parter' => '1035',
            'orderid' => '201710200000007313',
            'restate' => '-5',
            'ovalue' => '0.01',
            'sign' => 'c83917d82ea2195433903ab8e680dfe3',
            'attach' => '',
        ];

        $limapay = new Limapay();
        $limapay->setPrivateKey('1234');
        $limapay->setOptions($sourceData);
        $limapay->verifyOrderPayment([]);
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
            'parter' => '1035',
            'orderid' => '201710200000007313',
            'restate' => '0',
            'ovalue' => '0.01',
            'sign' => '67fbd7937cea7212ea73a7218da42cd6',
            'attach' => '',
        ];

        $entry = ['id' => '201606220000002806'];

        $limapay = new Limapay();
        $limapay->setPrivateKey('1234');
        $limapay->setOptions($sourceData);
        $limapay->verifyOrderPayment($entry);
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
            'parter' => '1035',
            'orderid' => '201710200000007313',
            'restate' => '0',
            'ovalue' => '0.01',
            'sign' => '67fbd7937cea7212ea73a7218da42cd6',
            'attach' => '',
        ];

        $entry = [
            'id' => '201710200000007313',
            'amount' => '1.0000',
        ];

        $limapay = new Limapay();
        $limapay->setPrivateKey('1234');
        $limapay->setOptions($sourceData);
        $limapay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回支付驗證成功
     */
    public function testReturnSuccess()
    {
        $sourceData = [
            'parter' => '1035',
            'orderid' => '201710200000007313',
            'restate' => '0',
            'ovalue' => '0.01',
            'sign' => '67fbd7937cea7212ea73a7218da42cd6',
            'attach' => '',
        ];

        $entry = [
            'id' => '201710200000007313',
            'amount' => '0.0100',
        ];

        $limapay = new Limapay();
        $limapay->setPrivateKey('1234');
        $limapay->setOptions($sourceData);
        $limapay->verifyOrderPayment($entry);

        $this->assertEquals('ok', $limapay->getMsg());
    }
}
