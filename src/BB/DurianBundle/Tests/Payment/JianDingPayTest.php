<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\JianDingPay;
use Buzz\Message\Response;

class JianDingPayTest extends DurianTestCase
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

        $jianDingPay = new JianDingPay();
        $jianDingPay->getVerifyData();
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

        $jianDingPay = new JianDingPay();
        $jianDingPay->setPrivateKey('test');
        $jianDingPay->setOptions([]);
        $jianDingPay->getVerifyData();
    }

    /**
     * 測試支付加密時代入支付平台不支援的銀行
     */
    public function testPayEncodeUnsupportedPaymentVendor()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'PaymentVendor is not supported by PaymentGateway',
            180066
        );

        $sourceData = [
            'number' => '9527',
            'paymentVendorId' => '999',
            'amount' => '0.01',
            'orderId' => '201709110000000001',
            'postUrl' => 'http://jainDingPay.com.tw',
            'notify_url' => 'http://yes9527.com.tw',
        ];

        $jianDingPay = new JianDingPay();
        $jianDingPay->setPrivateKey('test');
        $jianDingPay->setOptions($sourceData);
        $jianDingPay->getVerifyData();
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
            'number' => '9527',
            'paymentVendorId' => '1090',
            'amount' => '0.01',
            'orderId' => '201709110000000001',
            'postUrl' => '',
            'notify_url' => 'http://yes9527.com.tw',
        ];

        $jianDingPay = new JianDingPay();
        $jianDingPay->setPrivateKey('test');
        $jianDingPay->setOptions($sourceData);
        $jianDingPay->getVerifyData();
    }

    /**
     * 測試支付加密
     */
    public function testPay()
    {
        $sourceData = [
            'number' => '9527',
            'paymentVendorId' => '1090',
            'amount' => '0.01',
            'orderId' => '201709110000000001',
            'notify_url' => 'http://yes9527.com.tw',
            'postUrl' => 'http://jainDingPay.com.tw',
        ];

        $jianDingPay = new JianDingPay();
        $jianDingPay->setPrivateKey('test');
        $jianDingPay->setOptions($sourceData);
        $requestData = $jianDingPay->getVerifyData();

        $this->assertEquals($sourceData['number'], $requestData['parter']);
        $this->assertEquals('1004', $requestData['type']);
        $this->assertSame('0.01', $requestData['value']);
        $this->assertEquals($sourceData['orderId'], $requestData['orderid']);
        $this->assertEquals($sourceData['notify_url'], $requestData['callbackurl']);
        $this->assertEquals('6c9ad3cabf6041154560adda24846b31', $requestData['sign']);

        //檢查要提交的網址是否正確
        $data = [];
        $data['parter'] = $requestData['parter'];
        $data['type'] = $requestData['type'];
        $data['value'] = $requestData['value'];
        $data['orderid'] = $requestData['orderid'];
        $data['callbackurl'] = $requestData['callbackurl'];
        $data['sign'] = $requestData['sign'];

        $this->assertEquals($sourceData['postUrl'] . '?' . http_build_query($data), $requestData['act_url']);
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

        $jianDingPay = new JianDingPay();
        $jianDingPay->verifyOrderPayment([]);
    }

    /**
     * 測試解密驗證支付平台缺少回傳totalFee
     */
    public function testVerifyWithoutTotalFee()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $sourceData = [
            'orderId' => '201709110000000001',
            'resultCode' => '200',
            'resultMessage' => '成功',
            'notify_url' => 'http://www.yes9527.com.tw',
            'sign' => 'POWEIISSOGOODHAHA',
        ];

        $jianDingPay = new JianDingPay();
        $jianDingPay->setPrivateKey('test');
        $jianDingPay->setOptions($sourceData);
        $jianDingPay->verifyOrderPayment([]);
    }

    /**
     * 測試解密驗證支付平台缺少回傳orderId
     */
    public function testVerifyWithoutOrderId()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $sourceData = [
            'totalFee' => '0.01',
            'resultCode' => '200',
            'resultMessage' => '成功',
            'notify_url' => 'http://www.yes9527.com.tw',
            'sign' => 'POWEIISSOGOODHAHA',
        ];

        $jianDingPay = new JianDingPay();
        $jianDingPay->setPrivateKey('test');
        $jianDingPay->setOptions($sourceData);
        $jianDingPay->verifyOrderPayment([]);
    }

    /**
     * 測試解密驗證支付平台未指定返回參數
     */
    public function testVerifyWithNoReturnParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $sourceData = [
            'orderId' => '201709110000000001',
            'totalFee' => '0.01',
            'resultCode' => '200',
            'resultMessage' => '成功',
            'notify_url' => 'http://www.yes9527.com.tw',
            'sign' => 'POWEIISSOGOODHAHA',
        ];

        $jianDingPay = new JianDingPay();
        $jianDingPay->setPrivateKey('test');
        $jianDingPay->setOptions($sourceData);
        $jianDingPay->verifyOrderPayment([]);
    }

    /**
     * 測試解密驗證支付平台未指定返回參數
     */
    public function testVerifyWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $sourceData = [
            'type' => '1004',
            'orderId' => '201709110000000001',
            'totalFee' => '0.01',
            'resultCode' => '200',
            'resultMessage' => '成功',
            'notify_url' => 'http://www.yes9527.com.tw',
        ];

        $entry = ['merchant_number' => '9527'];

        $jianDingPay = new JianDingPay();
        $jianDingPay->setPrivateKey('test');
        $jianDingPay->setOptions($sourceData);
        $jianDingPay->verifyOrderPayment($entry);
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
            'type' => '1004',
            'orderId' => '201709110000000001',
            'totalFee' => '0.01',
            'resultCode' => '200',
            'resultMessage' => '成功',
            'notify_url' => 'http://www.yes9527.com.tw',
            'sign' => 'HAPPYHAPPYHAPPY',
        ];

        $entry = ['merchant_number' => '9527'];

        $jianDingPay = new JianDingPay();
        $jianDingPay->setPrivateKey('test');
        $jianDingPay->setOptions($sourceData);
        $jianDingPay->verifyOrderPayment($entry);
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
            'type' => '1004',
            'orderId' => '201709110000000001',
            'totalFee' => '0.01',
            'resultCode' => '999',
            'resultMessage' => '失敗',
            'notify_url' => 'http://www.yes9527.com.tw',
            'sign' => '287f5e4815f3b302b0c950644c7399ca',
        ];

        $entry = ['merchant_number' => '9527'];

        $jianDingPay = new JianDingPay();
        $jianDingPay->setPrivateKey('test');
        $jianDingPay->setOptions($sourceData);
        $jianDingPay->verifyOrderPayment($entry);
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
            'type' => '1004',
            'orderId' => '201709110000000001',
            'totalFee' => '0.01',
            'resultCode' => '200',
            'resultMessage' => '成功',
            'notify_url' => 'http://www.yes9527.com.tw',
            'sign' => '287f5e4815f3b302b0c950644c7399ca',
        ];

        $entry = [
            'merchant_number' => '9527',
            'id' => '201709110000000002',
        ];

        $jianDingPay = new JianDingPay();
        $jianDingPay->setPrivateKey('test');
        $jianDingPay->setOptions($sourceData);
        $jianDingPay->verifyOrderPayment($entry);
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
            'type' => '1004',
            'orderId' => '201709110000000001',
            'totalFee' => '0.01',
            'resultCode' => '200',
            'resultMessage' => '成功',
            'notify_url' => 'http://www.yes9527.com.tw',
            'sign' => '287f5e4815f3b302b0c950644c7399ca',
        ];

        $entry = [
            'merchant_number' => '9527',
            'id' => '201709110000000001',
            'amount' => '5.00',
        ];

        $jianDingPay = new JianDingPay();
        $jianDingPay->setPrivateKey('test');
        $jianDingPay->setOptions($sourceData);
        $jianDingPay->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testPaySuccess()
    {
        $sourceData = [
            'type' => '1004',
            'orderId' => '201709110000000001',
            'totalFee' => '0.01',
            'resultCode' => '200',
            'resultMessage' => '成功',
            'notify_url' => 'http://www.yes9527.com.tw',
            'sign' => '287f5e4815f3b302b0c950644c7399ca',
        ];

        $entry = [
            'merchant_number' => '9527',
            'id' => '201709110000000001',
            'amount' => '0.01',
        ];

        $jianDingPay = new JianDingPay();
        $jianDingPay->setPrivateKey('test');
        $jianDingPay->setOptions($sourceData);
        $jianDingPay->verifyOrderPayment($entry);

        $this->assertEquals('opstate=0', $jianDingPay->getMsg());
    }
}
