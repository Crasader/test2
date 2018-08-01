<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Payment\DeShiPay;
use BB\DurianBundle\Tests\DurianTestCase;
use Buzz\Message\Response;

class DeShiPayTest extends DurianTestCase
{
    /**
     * 私鑰
     *
     * @var string
     */
    private $privateKey;

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

        $config = [
            'private_key_bits' => 1024,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ];

        // Create the keypair
        $res = openssl_pkey_new($config);

        $privkey = '';

        // Get private key
        openssl_pkey_export($res, $privkey);

        $this->privateKey = base64_encode($privkey);

        // Get public key
        $pubkey = openssl_pkey_get_details($res);

        $publicKey = base64_encode($pubkey['key']);

        $this->option = [
            'number' => '9527',
            'orderId' => '201807020000046582',
            'orderCreateDate' => '2018-07-02 19:24:56',
            'amount' => '0.01',
            'notify_url' => 'http://www.seafood.help/',
            'paymentVendorId' => '1',
            'rsa_private_key' => $this->privateKey,
        ];

        $this->returnResult = [
            'resp_msg' => 'Success!',
            'order_no' => '201807020000046582',
            'version' => '1.0',
            'currency' => '156',
            'amount' => '1',
            'mer_code' => '9527',
            'trans_code' => '03',
            'resp_code' => '00',
            'sign_method' => '01',
            'stlm_date' => '0629',
            'txn_date' => '20180702192456',
        ];

        $this->returnResult['sign'] = $this->rsaPrivateKeyEncrypt($this->returnResult);
        $this->returnResult['rsa_public_key'] = $publicKey;
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

        $deShiPay = new DeShiPay();
        $deShiPay->getVerifyData();
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

        $this->option['paymentVendorId'] = '999';

        $deShiPay = new DeShiPay();
        $deShiPay->setOptions($this->option);
        $deShiPay->getVerifyData();
    }

    /**
     * 測試支付時取得RSA私鑰為空字串
     */
    public function testPayGetRsaPrivateKeyEmpty()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Rsa private key is empty',
            180092
        );

        $this->option['rsa_private_key'] = '';

        $deShiPay = new DeShiPay();
        $deShiPay->setOptions($this->option);
        $deShiPay->getVerifyData();
    }

    /**
     * 測試支付時取得RSA私鑰失敗
     */
    public function testPayGetRsaPrivateKeyError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Get rsa private key failure',
            180093
        );

        $this->option['rsa_private_key'] = '123456';

        $deShiPay = new DeShiPay();
        $deShiPay->setOptions($this->option);
        $deShiPay->getVerifyData();
    }

    /**
     * 測試支付時產生簽名失敗
     */
    public function testPayGenerateSignatureFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Generate signature failure',
            180144
        );

        $config = [
            'private_key_bits' => 384,
            'private_key_type' => OPENSSL_KEYTYPE_DH,
        ];

        // Create the keypair
        $res = openssl_pkey_new($config);

        $privkey = '';

        // Get private key
        openssl_pkey_export($res, $privkey);

        $this->option['rsa_private_key'] = base64_encode($privkey);

        $deShiPay = new DeShiPay();
        $deShiPay->setOptions($this->option);
        $deShiPay->getVerifyData();
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $deShiPay = new DeShiPay();
        $deShiPay->setOptions($this->option);
        $data = $deShiPay->getVerifyData();

        $encodeData = [
            'version' => '1.0',
            'sign_method' => '01',
            'trans_code' => '03',
            'mer_code' => '9527',
            'order_no' => '201807020000046582',
            'txn_date' => '20180702192456',
            'amount' => '1',
            'currency' => '156',
            'front_url' => 'http://www.seafood.help/',
            'back_url' => 'http://www.seafood.help/',
            'bank_code' => 'ICBC',
        ];
        $sign = $this->rsaPrivateKeyEncrypt($encodeData);

        $this->assertEquals('1.0', $data['version']);
        $this->assertEquals('01', $data['sign_method']);
        $this->assertEquals($sign, $data['sign']);
        $this->assertEquals('03', $data['trans_code']);
        $this->assertEquals('9527', $data['mer_code']);
        $this->assertEquals('201807020000046582', $data['order_no']);
        $this->assertEquals('20180702192456', $data['txn_date']);
        $this->assertEquals('1', $data['amount']);
        $this->assertEquals('156', $data['currency']);
        $this->assertEquals('http://www.seafood.help/', $data['front_url']);
        $this->assertEquals('http://www.seafood.help/', $data['back_url']);
        $this->assertEquals('ICBC', $data['bank_code']);
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

        $deShiPay = new DeShiPay();
        $deShiPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時未返回簽名
     */
    public function testReturnWithoutSignature()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        unset($this->returnResult['sign']);

        $deShiPay = new DeShiPay();
        $deShiPay->setOptions($this->returnResult);
        $deShiPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時取得商家公鑰為空字串
     */
    public function testReturnGetRsaPublicKeyEmpty()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Rsa public key is empty',
            180095
        );

        $this->returnResult['rsa_public_key'] = '';

        $deShiPay = new DeShiPay();
        $deShiPay->setOptions($this->returnResult);
        $deShiPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時取得商家公鑰失敗
     */
    public function testReturnGetRsaPublicKeyError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Get rsa public key failure',
            180096
        );

        $this->returnResult['rsa_public_key'] = '123456';

        $deShiPay = new DeShiPay();
        $deShiPay->setOptions($this->returnResult);
        $deShiPay->verifyOrderPayment([]);
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

        $this->returnResult['sign'] = '08aba0d9';

        $deShiPay = new DeShiPay();
        $deShiPay->setOptions($this->returnResult);
        $deShiPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時支付失敗
     */
    public function testReturnWithPaymentFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $this->returnResult['resp_code'] = '02';

        $encodeData = $this->returnResult;
        unset($encodeData['rsa_public_key']);
        unset($encodeData['sign']);

        $this->returnResult['sign'] = $this->rsaPrivateKeyEncrypt($encodeData);

        $deShiPay = new DeShiPay();
        $deShiPay->setOptions($this->returnResult);
        $deShiPay->verifyOrderPayment([]);
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

        $entry = ['id' => '9453'];

        $deShiPay = new DeShiPay();
        $deShiPay->setOptions($this->returnResult);
        $deShiPay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果為金額錯誤
     */
    public function testReturnOrderAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $entry = [
            'id' => '201807020000046582',
            'amount' => '123',
        ];

        $deShiPay = new DeShiPay();
        $deShiPay->setOptions($this->returnResult);
        $deShiPay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果
     */
    public function testReturn()
    {
        $entry = [
            'id' => '201807020000046582',
            'amount' => '0.01',
        ];

        $deShiPay = new DeShiPay();
        $deShiPay->setOptions($this->returnResult);
        $deShiPay->verifyOrderPayment($entry);

        $this->assertEquals('success', $deShiPay->getMsg());
    }

    /**
     * RSA私鑰加密
     *
     * @param array $encodeData 欲加密的陣列
     * @return string
     */
    private function rsaPrivateKeyEncrypt($encodeData)
    {
        $content = trim(base64_decode($this->privateKey));
        $privateKey = openssl_pkey_get_private($content);

        ksort($encodeData);

        $encodeStr = urldecode(http_build_query($encodeData));
        $encodeStr = sha1($encodeStr);

        $sign = '';
        openssl_sign($encodeStr, $sign, $privateKey, OPENSSL_ALGO_SHA1);

        return bin2hex($sign);
    }
}
