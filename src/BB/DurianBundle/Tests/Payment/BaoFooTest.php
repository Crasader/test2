<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\BaoFoo;

/**
 * @author sweet <pigsweet7834@gmail.com>
 */
class BaoFooTest extends DurianTestCase
{
    /**
     * 測試加密基本參數設定沒有帶入privateKey的情況
     */
    public function testSetEncodeSourceNoPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $baoFoo = new BaoFoo();
        $baoFoo->getVerifyData();
    }

    /**
     * 測試加密基本參數設定未指定支付參數
     */
    public function testSetEncodeSourceNoReturnParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No pay parameter specified',
            180145
        );

        $baoFoo = new BaoFoo();
        $baoFoo->setPrivateKey('1234');

        $sourceData = ['number' => ''];

        $baoFoo->setOptions($sourceData);
        $baoFoo->getVerifyData();
    }

    /**
     * 測試加密
     */
    public function testGetEncodeData()
    {
        $sourceData = [
            'number' => '03358',
            'orderCreateDate' => '20131128000000',
            'orderId' => '2',
            'amount' => '10',
            'username' => '張三',
            'notify_url' => 'http://esball.org/app/member/pay_online2/pay_result.php',
            'merchantId' => '12345',
            'domain' => '6',
        ];

        $baoFoo = new BaoFoo();
        $baoFoo->setPrivateKey('1234');
        $baoFoo->setOptions($sourceData);
        $encodeData = $baoFoo->getVerifyData();

        $notifyUrl = sprintf(
            '%s?pay_system=%s&hallid=%s',
            $sourceData['notify_url'],
            $sourceData['merchantId'],
            $sourceData['domain']
        );

        $this->assertEquals('03358', $encodeData['MerchantID']);
        $this->assertEquals('20131128000000', $encodeData['TradeDate']);
        $this->assertEquals('2', $encodeData['TransID']);
        $this->assertEquals('1000.00', $encodeData['OrderMoney']);
        $this->assertEquals('張三', $encodeData['ProductName']);
        $this->assertEquals('張三', $encodeData['Username']);
        $this->assertEquals($notifyUrl, $encodeData['Merchant_url']);
        $this->assertEquals($notifyUrl, $encodeData['Return_url']);
        $this->assertEquals('f98c416a14bffb36ee6ef3b94416a590', $encodeData['Md5Sign']);
    }

    /**
     * 測試解密基本參數設定沒有帶入key的情況
     */
    public function testSetDecodeSourceNoKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $baoFoo = new BaoFoo();

        $baoFoo->verifyOrderPayment([]);
    }

    /**
     * 測試解密驗證時沒有支付平台缺少回傳參數(測試Result:交易狀態)
     */
    public function testVerifyNoReturnParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $baoFoo = new BaoFoo();
        $baoFoo->setPrivateKey('1234');

        $sourceData = [
            'MerchantID'     => '03358',
            'TransID'        => '2',
            'resultDesc'     => '3',
            'factMoney'      => '100.00',
            'additionalInfo' => '附加訊息',
            'SuccTime'       => '20131128000000',
            'Md5Sign'        => 'c31a6b799ff4fda80fb2b94e8186ccce'
        ];

        $baoFoo->setOptions($sourceData);
        $baoFoo->verifyOrderPayment([]);
    }

    /**
     * 測試解密驗證時沒有支付平台缺少回傳參數(測試Md5Sign:加密簽名)
     */
    public function testVerifyWithoutMd5Sign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $baoFoo = new BaoFoo();
        $baoFoo->setPrivateKey('1234');

        $sourceData = [
            'MerchantID'     => '03358',
            'TransID'        => '2',
            'Result'         => '1',
            'resultDesc'     => '3',
            'factMoney'      => '100.00',
            'additionalInfo' => '附加訊息',
            'SuccTime'       => '20131128000000'
        ];

        $baoFoo->setOptions($sourceData);
        $baoFoo->verifyOrderPayment([]);
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

        $baoFoo = new BaoFoo();
        $baoFoo->setPrivateKey('1234');

        $sourceData = [
            'MerchantID'     => '03358',
            'TransID'        => '2',
            'Result'         => '1',
            'resultDesc'     => '3',
            'factMoney'      => '1000.00',
            'additionalInfo' => '附加訊息',
            'SuccTime'       => '20131128000000',
            'Md5Sign'        => '97b252d52ed773eaafe47276d1c99f4f'
        ];

        $baoFoo->setOptions($sourceData);
        $baoFoo->verifyOrderPayment([]);
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

        $baoFoo = new BaoFoo();
        $baoFoo->setPrivateKey('1234');

        $sourceData = [
            'MerchantID'     => '03358',
            'TransID'        => '2',
            'Result'         => '2',
            'resultDesc'     => '3',
            'factMoney'      => '1000.00',
            'additionalInfo' => '附加訊息',
            'SuccTime'       => '20131128000000',
            'Md5Sign'        => '98db0dd1e2bc75b872c58c1955ca4a8b'
        ];

        $baoFoo->setOptions($sourceData);
        $baoFoo->verifyOrderPayment([]);
    }

    /**
     * 測試返回時支付失敗(支付結果描述不為01)
     */
    public function testReturnPaymentError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $baoFoo = new BaoFoo();
        $baoFoo->setPrivateKey('1234');

        $sourceData = [
            'MerchantID'     => '03358',
            'TransID'        => '2',
            'Result'         => '1',
            'resultDesc'     => '1',
            'factMoney'      => '1000.00',
            'additionalInfo' => '附加訊息',
            'SuccTime'       => '20131128000000',
            'Md5Sign'        => '2b7c22b83ce65f7d87e35e319c72e699'
        ];

        $baoFoo->setOptions($sourceData);
        $baoFoo->verifyOrderPayment([]);
    }

    /**
     * 測試線上支付單號不正確的情況
     */
    public function testOrderIdError()
    {
        $this->setExpectedException('BB\DurianBundle\Exception\PaymentException', 'Order Id error', 180061);

        $baoFoo = new BaoFoo();
        $baoFoo->setPrivateKey('1234');

        $sourceData = [
            'MerchantID'     => '03358',
            'TransID'        => '2',
            'Result'         => '1',
            'resultDesc'     => '01',
            'factMoney'      => '100.00',
            'additionalInfo' => '附加訊息',
            'SuccTime'       => '20131128000000',
            'Md5Sign'        => 'd16b4adcaa8211fb22d1c09adcea97a5'
        ];

        $entry = ['id' => '20140113143143'];

        $baoFoo->setOptions($sourceData);
        $baoFoo->verifyOrderPayment($entry);
    }

    /**
     * 測試金額比對錯誤的情況
     */
    public function testAmountFailure()
    {
        $this->setExpectedException('BB\DurianBundle\Exception\PaymentException', 'Order Amount error', 180058);

        $baoFoo = new BaoFoo();
        $baoFoo->setPrivateKey('1234');

        $sourceData = [
            'MerchantID'     => '03358',
            'TransID'        => '2',
            'Result'         => '1',
            'resultDesc'     => '01',
            'factMoney'      => '100.00',
            'additionalInfo' => '附加訊息',
            'SuccTime'       => '20131128000000',
            'Md5Sign'        => 'd16b4adcaa8211fb22d1c09adcea97a5'
        ];

        $entry = [
            'id' => '2',
            'amount' => '10.00'
        ];

        $baoFoo->setOptions($sourceData);
        $baoFoo->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testPaySuccess()
    {
        $baoFoo = new BaoFoo();
        $baoFoo->setPrivateKey('1234');

        $sourceData = [
            'MerchantID'     => '03358',
            'TransID'        => '2',
            'Result'         => '1',
            'resultDesc'     => '01',
            'factMoney'      => '100.00',
            'additionalInfo' => '附加訊息',
            'SuccTime'       => '20131128000000',
            'Md5Sign'        => 'd16b4adcaa8211fb22d1c09adcea97a5'
        ];

        $entry = [
            'id' => '2',
            'amount' => '1.00'
        ];

        $baoFoo->setOptions($sourceData);
        $baoFoo->verifyOrderPayment($entry);

        $this->assertEquals('OK', $baoFoo->getMsg());
    }
}
