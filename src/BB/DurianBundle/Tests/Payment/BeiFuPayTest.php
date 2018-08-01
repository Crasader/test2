<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\BeiFuPay;

class BeiFuPayTest extends DurianTestCase
{
    /**
     * 測試支付時缺少私鑰
     */
    public function testPayWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $beiFuPay = new BeiFuPay();
        $beiFuPay->getVerifyData();
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

        $beiFuPay = new BeiFuPay();
        $beiFuPay->setPrivateKey('test');
        $beiFuPay->getVerifyData();
    }

    /**
     * 測試支付設定帶入不支援的銀行
     */
    public function testPaySourceNotSupportBank()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'PaymentVendor is not supported by PaymentGateway',
            180066
        );

        $options = [
            'number' => 'D201712292228',
            'orderId' => '201801150000003708',
            'amount' => '2',
            'notify_url' => 'http://pay.my/pay/return.php',
            'paymentVendorId' => '999',
        ];

        $beiFuPay = new BeiFuPay();
        $beiFuPay->setPrivateKey('test');
        $beiFuPay->setOptions($options);
        $beiFuPay->getVerifyData();
    }

    /**
     * 測試支付缺少商家額外的參數設定desKey
     */
    public function testPayWithoutMerchantExtraDesKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No merchant extra value specified',
            180143
        );

        $options = [
            'number' => 'D201712292228',
            'orderId' => '201801150000003708',
            'amount' => '2',
            'notify_url' => 'http://pay.my/pay/return.php',
            'paymentVendorId' => '1',
            'merchant_extra' => ['pSyspwd' => '123456'],
        ];

        $beiFuPay = new BeiFuPay();
        $beiFuPay->setPrivateKey('test');
        $beiFuPay->setOptions($options);
        $beiFuPay->getVerifyData();
    }

    /**
     * 測試支付缺少商家額外的參數設定pSyspwd
     */
    public function testPayWithoutMerchantExtraPSyspwd()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No merchant extra value specified',
            180143
        );

        $options = [
            'number' => 'D201712292228',
            'orderId' => '201801150000003708',
            'amount' => '2',
            'notify_url' => 'http://pay.my/pay/return.php',
            'paymentVendorId' => '1',
            'merchant_extra' => ['desKey' => 'asd123'],
        ];

        $beiFuPay = new BeiFuPay();
        $beiFuPay->setPrivateKey('test');
        $beiFuPay->setOptions($options);
        $beiFuPay->getVerifyData();
    }

    /**
     * 測試支付DES加密失敗
     */
    public function testPayButDESEncryptFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'DES encrypt failed',
            150180177
        );

        $options = [
            'number' => 'D201712292228',
            'orderId' => '201801150000003708',
            'amount' => '2',
            'notify_url' => 'http://pay.my/pay/return.php',
            'paymentVendorId' => '1',
            'merchant_extra' => [
                'pSyspwd' => '123456',
                'desKey' => 'asd123'
            ],
        ];

        $beiFuPay = new BeiFuPay();
        $beiFuPay->setPrivateKey('test');
        $beiFuPay->setOptions($options);
        $beiFuPay->getVerifyData();
    }

    /**
     * 測試二維支付
     */
    public function testQrCodePay()
    {
        $options = [
            'number' => 'D201712292228',
            'orderId' => '201801150000003708',
            'amount' => '2',
            'notify_url' => 'http://pay.my/pay/return.php',
            'paymentVendorId' => '1090',
            'merchant_extra' => [
                'pSyspwd' => '123456',
                'desKey' => 'asd12345'
            ],
            'postUrl' => 'http://way.yf52.com/api/pay',
        ];

        $params = 'L4J7UqS6UWPq3V21sA5rbQByD%2BBgu2Mzw8aX8YddI0YbWDJusPiQZheAZREn71yFN89uumQyCfwtzGaFqRax6%2BOAA' .
            'MNcUo1z%2FBP4X%2B7UO3LU5XAPhBI4fKru97hX4b%2Bqvmi10y%2Fv1bTMHmIzp963EYC1yv16oYmxpNbMIK7lSg7DNE%2F0Rjj' .
            'wEoFpa2IoVVp8UaYc3Ieu5ifa19bjN9vKA1va0VATPfqDtJsTPifMtNg%3D';

        $beiFuPay = new BeiFuPay();
        $beiFuPay->setPrivateKey('test');
        $beiFuPay->setOptions($options);
        $data = $beiFuPay->getVerifyData();

        $postUrl = 'http://way.yf52.com/api/pay?params=' . $params . '&uname=D201712292228';
        $this->assertEquals($postUrl, $data['post_url']);
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $options = [
            'number' => 'D201712292228',
            'orderId' => '201801150000003708',
            'amount' => '2',
            'notify_url' => 'http://pay.my/pay/return.php',
            'paymentVendorId' => '1',
            'merchant_extra' => [
                'pSyspwd' => '123456',
                'desKey' => 'asd12345'
            ],
            'postUrl' => 'http://way.yf52.com/api/pay',
        ];

        $params = 'L4J7UqS6UWPq3V21sA5rbQByD%2BBgu2Mzw8aX8YddI0brKwe5bxhPRHLibx%2BDVwgwYtLNJXBbbJHRYVjqf17kKLAPrPj' .
            'jCCrnvFWNwGo1IZx5f1sDvul2w3%2BmvJmaBvXFJgOwFSuVvM%2FuoBlZrZhzn7wJ6BKxv2mOFzmIEdTP%2Fhdt09v%2Fqb2%2BNcM' .
            'xUIU50Y5l4iTfEI8R5SOZDUL0NNVz6ny9hXrKoYeBKDacgx7SmfFAJXt11ZCSCQ%3D%3D';

        $beiFuPay = new BeiFuPay();
        $beiFuPay->setPrivateKey('test');
        $beiFuPay->setOptions($options);
        $data = $beiFuPay->getVerifyData();

        $postUrl = 'http://way.yf52.com/api/pay?params=' . $params . '&uname=D201712292228';
        $this->assertEquals($postUrl, $data['post_url']);
    }

    /**
     * 測試返回時缺少私鑰
     */
    public function testReturnWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $beiFuPay = new BeiFuPay();
        $beiFuPay->verifyOrderPayment([]);
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

        $beiFuPay = new BeiFuPay();
        $beiFuPay->setPrivateKey('test');
        $beiFuPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時缺少加密簽名
     */
    public function testReturnWithoutSignMsg()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $merchantExtra = [
            'pSyspwd' => '123456',
        ];

        $options = [
            'p_oid' => '201801150000003708',
            'p_money' => '2.0',
            'p_code' => '1',
            'p_remarks' => '',
            'p_sysid' => 'HTF1516002315282',
            'p_syspwd' => '044afb98d29cf157e8716be679c51509',
            'merchant_extra' => $merchantExtra,
        ];

        $entry = [
            'merchant_number' => 'D201712292228',
        ];

        $beiFuPay = new BeiFuPay();
        $beiFuPay->setPrivateKey('test');
        $beiFuPay->setOptions($options);
        $beiFuPay->verifyOrderPayment($entry);
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

        $merchantExtra = [
            'pSyspwd' => '123456',
        ];

        $options = [
            'p_oid' => '201801150000003708',
            'p_money' => '2.0',
            'p_code' => '1',
            'p_remarks' => '',
            'p_sysid' => 'HTF1516002315282',
            'p_syspwd' => '044afb98d29cf157e8716be679c51509',
            'merchant_extra' => $merchantExtra,
            'p_md5' => '5fe1e8ff5914f1696cefe2e21be6b010',
        ];

        $entry = [
            'merchant_number' => 'D201712292228',
        ];

        $beiFuPay = new BeiFuPay();
        $beiFuPay->setPrivateKey('test');
        $beiFuPay->setOptions($options);
        $beiFuPay->verifyOrderPayment($entry);
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

        $merchantExtra = [
            'pSyspwd' => '123456',
        ];

        $options = [
            'p_oid' => '201801150000003708',
            'p_money' => '2.0',
            'p_code' => '0',
            'p_remarks' => '',
            'p_sysid' => 'HTF1516002315282',
            'p_syspwd' => '044afb98d29cf157e8716be679c51509',
            'merchant_extra' => $merchantExtra,
            'p_md5' => '1175f04cbc9fb2590faf3ecb609373f0',
        ];

        $entry = [
            'merchant_number' => 'D201712292228',
        ];

        $beiFuPay = new BeiFuPay();
        $beiFuPay->setPrivateKey('test');
        $beiFuPay->setOptions($options);
        $beiFuPay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果為訂單號錯誤
     */
    public function testReturnOrderIdError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $merchantExtra = [
            'pSyspwd' => '123456',
        ];

        $options = [
            'p_oid' => '201801150000003708',
            'p_money' => '2.0',
            'p_code' => '1',
            'p_remarks' => '',
            'p_sysid' => 'HTF1516002315282',
            'p_syspwd' => '044afb98d29cf157e8716be679c51509',
            'merchant_extra' => $merchantExtra,
            'p_md5' => '1175f04cbc9fb2590faf3ecb609373f0',
        ];

        $entry = [
            'merchant_number' => 'D201712292228',
            'id' => '201801150000003709',
            'amount' => '2',
        ];

        $beiFuPay = new BeiFuPay();
        $beiFuPay->setPrivateKey('test');
        $beiFuPay->setOptions($options);
        $beiFuPay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果為訂單金額錯誤
     */
    public function testReturnOrderAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $merchantExtra = [
            'pSyspwd' => '123456',
        ];

        $options = [
            'p_oid' => '201801150000003708',
            'p_money' => '2.0',
            'p_code' => '1',
            'p_remarks' => '',
            'p_sysid' => 'HTF1516002315282',
            'p_syspwd' => '044afb98d29cf157e8716be679c51509',
            'merchant_extra' => $merchantExtra,
            'p_md5' => '1175f04cbc9fb2590faf3ecb609373f0',
        ];

        $entry = [
            'merchant_number' => 'D201712292228',
            'id' => '201801150000003708',
            'amount' => '0.02',
        ];

        $beiFuPay = new BeiFuPay();
        $beiFuPay->setPrivateKey('test');
        $beiFuPay->setOptions($options);
        $beiFuPay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果
     */
    public function testReturnOrder()
    {
        $merchantExtra = [
            'pSyspwd' => '123456',
        ];

        $options = [
            'p_oid' => '201801150000003708',
            'p_money' => '2.0',
            'p_code' => '1',
            'p_remarks' => '',
            'p_sysid' => 'HTF1516002315282',
            'p_syspwd' => '044afb98d29cf157e8716be679c51509',
            'merchant_extra' => $merchantExtra,
            'p_md5' => '1175f04cbc9fb2590faf3ecb609373f0',
        ];

        $entry = [
            'merchant_number' => 'D201712292228',
            'id' => '201801150000003708',
            'amount' => '2',
        ];

        $beiFuPay = new BeiFuPay();
        $beiFuPay->setPrivateKey('test');
        $beiFuPay->setOptions($options);
        $beiFuPay->verifyOrderPayment($entry);

        $this->assertEquals('success', $beiFuPay->getMsg());
    }
}
