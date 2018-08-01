<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Payment\RongCanPay;
use BB\DurianBundle\Tests\DurianTestCase;
use Buzz\Message\Response;

class RongCanPayTest extends DurianTestCase
{
    /**
     * 私鑰
     *
     * @var string
     */
    private $privateKey;

    /**
     * 公鑰
     *
     * @var string
     */
    private $publicKey;

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

        $this->publicKey = base64_encode($pubkey['key']);

        $this->option = [
            'number' => '9527',
            'orderId' => '201806130000046462',
            'amount' => '10',
            'paymentVendorId' => '1102',
            'notify_url' => 'http://www.seafood.help/',
            'rsa_private_key' => $this->privateKey,
        ];

        $this->returnResult = [
            'merchantCode' => 'M0000001',
            'orderNo' => '201806130000046462',
            'amount' => '100',
            'successAmt' => '100',
            'payOrderNo' => '53299',
            'orderStatus' => 'Success',
            'extraReturnParam' => '',
        ];

        $this->returnResult['sign'] = $this->rsaPrivateKeyEncrypt($this->returnResult);
        $this->returnResult['signType'] = 'RSA';
        $this->returnResult['rsa_public_key'] = $this->publicKey;
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

        $rongCanPay = new RongCanPay();
        $rongCanPay->getVerifyData();
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

        $rongCanPay = new RongCanPay();
        $rongCanPay->setOptions($this->option);
        $rongCanPay->getVerifyData();
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

        $rongCanPay = new RongCanPay();
        $rongCanPay->setOptions($this->option);
        $rongCanPay->getVerifyData();
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

        $rongCanPay = new RongCanPay();
        $rongCanPay->setOptions($this->option);
        $rongCanPay->getVerifyData();
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

        $rongCanPay = new RongCanPay();
        $rongCanPay->setOptions($this->option);
        $rongCanPay->getVerifyData();
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $rongCanPay = new RongCanPay();
        $rongCanPay->setOptions($this->option);
        $data = $rongCanPay->getVerifyData();

        $encodeData = [
            'charset' => 'UTF-8',
            'merchantCode' => '9527',
            'orderNo' => '201806130000046462',
            'amount' => '1000',
            'channel' => 'BANK',
            'bankCode' => 'CASHIER',
            'remark' => '201806130000046462',
            'notifyUrl' => 'http://www.seafood.help/',
            'returnUrl' => '',
            'extraReturnParam' => '',
        ];
        $sign = $this->rsaPrivateKeyEncrypt($encodeData);

        $this->assertEquals('UTF-8', $data['charset']);
        $this->assertEquals('9527', $data['merchantCode']);
        $this->assertEquals('201806130000046462', $data['orderNo']);
        $this->assertEquals('1000', $data['amount']);
        $this->assertEquals('BANK', $data['channel']);
        $this->assertEquals('CASHIER', $data['bankCode']);
        $this->assertEquals('201806130000046462', $data['remark']);
        $this->assertEquals('http://www.seafood.help/', $data['notifyUrl']);
        $this->assertEquals('', $data['returnUrl']);
        $this->assertEquals('', $data['extraReturnParam']);
        $this->assertEquals('RSA', $data['signType']);
        $this->assertEquals($sign, $data['sign']);
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

        $rongCanPay = new RongCanPay();
        $rongCanPay->verifyOrderPayment([]);
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

        $rongCanPay = new RongCanPay();
        $rongCanPay->setOptions($this->returnResult);
        $rongCanPay->verifyOrderPayment([]);
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

        $rongCanPay = new RongCanPay();
        $rongCanPay->setOptions($this->returnResult);
        $rongCanPay->verifyOrderPayment([]);
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

        $rongCanPay = new RongCanPay();
        $rongCanPay->setOptions($this->returnResult);
        $rongCanPay->verifyOrderPayment([]);
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

        $this->returnResult['sign'] = 'dGYR2LiTLa+A6rX+PZ07C0c2PiYbdF/1g+YttHpdV2TE9NE8UUM';

        $rongCanPay = new RongCanPay();
        $rongCanPay->setOptions($this->returnResult);
        $rongCanPay->verifyOrderPayment([]);
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

        $this->returnResult['orderStatus'] = 'Fail';

        $encodeData = $this->returnResult;
        unset($encodeData['signType']);
        unset($encodeData['rsa_public_key']);
        unset($encodeData['sign']);

        $this->returnResult['sign'] = $this->rsaPrivateKeyEncrypt($encodeData);

        $rongCanPay = new RongCanPay();
        $rongCanPay->setOptions($this->returnResult);
        $rongCanPay->verifyOrderPayment([]);
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

        $rongCanPay = new RongCanPay();
        $rongCanPay->setOptions($this->returnResult);
        $rongCanPay->verifyOrderPayment($entry);
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
            'id' => '201806130000046462',
            'amount' => '123',
        ];

        $rongCanPay = new RongCanPay();
        $rongCanPay->setOptions($this->returnResult);
        $rongCanPay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果
     */
    public function testReturn()
    {
        $entry = [
            'id' => '201806130000046462',
            'amount' => '1',
        ];

        $rongCanPay = new RongCanPay();
        $rongCanPay->setOptions($this->returnResult);
        $rongCanPay->verifyOrderPayment($entry);

        $this->assertEquals('SUCCESS', $rongCanPay->getMsg());
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

        $encodeStr = urldecode(http_build_query($encodeData));

        $sign = '';
        openssl_sign($encodeStr, $sign, $privateKey, OPENSSL_ALGO_SHA1);

        return base64_encode($sign);
    }
}
