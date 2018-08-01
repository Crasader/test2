<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\NewEPayBank;

class NewEPayBankTest extends DurianTestCase
{
    /**
     * 私鑰
     *
     * @var string
     */
    private $rsaPrivateKey;

    /**
     * 公鑰
     *
     * @var string
     */
    private $rsaPublicKey;

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

        $this->rsaPrivateKey = base64_encode($privkey);

        // Get public key
        $pubkey = openssl_pkey_get_details($res);

        $this->rsaPublicKey = base64_encode($pubkey['key']);
    }

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

        $newEPayBank = new NewEPayBank();
        $newEPayBank->getVerifyData();
    }

    /**
     * 測試加密基本參數設定未指定支付參數
     */
    public function testPayWithNoPayParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No pay parameter specified',
            180145
        );

        $newEPayBank = new NewEPayBank();

        $newEPayBank->setPrivateKey('1234');
        $newEPayBank->getVerifyData();
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

        $sourceData = [
            'orderId' => '201709070000000831',
            'number' => '123456',
            'paymentVendorId' => '1234',
            'amount' => '100',
            'username' => 'php1test',
            'notify_url' => 'http://payment/return.php',
        ];

        $newEPayBank = new NewEPayBank();
        $newEPayBank->setPrivateKey('1234');
        $newEPayBank->setOptions($sourceData);
        $newEPayBank->getVerifyData();
    }

    /**
     * 測試支付沒代入orderCreateDate
     */
    public function testPayWithoutOrderCreateDate()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No pay parameter specified',
            180145
        );

        $sourceData = [
            'orderId' => '201709070000000831',
            'number' => '123456',
            'paymentVendorId' => '1',
            'amount' => '100',
            'username' => 'php1test',
            'notify_url' => 'http://payment/return.php',
            'orderCreateDate' => '',
            'rsa_public_key' => $this->rsaPublicKey,
        ];

        $newEPayBank = new NewEPayBank();
        $newEPayBank->setPrivateKey('1234');
        $newEPayBank->setOptions($sourceData);
        $newEPayBank->getVerifyData();
    }

    /**
     * 測試支付時取得RSA公鑰為空
     */
    public function testPayGetRsaPublicKeyEmpty()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Rsa public key is empty',
            180095
        );

        $sourceData = [
            'orderId' => '201709070000000831',
            'number' => '123456',
            'paymentVendorId' => '1',
            'amount' => '100',
            'username' => 'php1test',
            'notify_url' => 'http://payment/return.php',
            'orderCreateDate' => '2017-09-08 10:30:20',
            'rsa_public_key' => '',
        ];

        $newEPayBank = new NewEPayBank();
        $newEPayBank->setPrivateKey('1234');
        $newEPayBank->setOptions($sourceData);
        $newEPayBank->getVerifyData();
    }

    /**
     * 測試支付時取得RSA公鑰失敗
     */
    public function testPayGetRsaPublicKeyError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Get rsa public key failure',
            180096
        );

        $sourceData = [
            'orderId' => '201709070000000831',
            'number' => '123456',
            'paymentVendorId' => '1',
            'amount' => '100',
            'username' => 'php1test',
            'notify_url' => 'http://payment/return.php',
            'orderCreateDate' => '2017-09-08 10:30:20',
            'rsa_public_key' => 'public_key_test',
        ];

        $newEPayBank = new NewEPayBank();
        $newEPayBank->setPrivateKey('1234');
        $newEPayBank->setOptions($sourceData);
        $newEPayBank->getVerifyData();
    }

    /**
     * 測試加密
     */
    public function testGetEncodeData()
    {
        $sourceData = [
            'orderId' => '201709070000000831',
            'number' => '123456',
            'paymentVendorId' => '1',
            'amount' => '100',
            'username' => 'php1test',
            'postUrl' => 'newepay.online/',
            'notify_url' => 'http://payment/return.php',
            'orderCreateDate' => '2017-09-08 10:30:20',
            'rsa_public_key' => $this->rsaPublicKey,
        ];

        $newEPayBank = new NewEPayBank();
        $newEPayBank->setPrivateKey('1234');
        $newEPayBank->setOptions($sourceData);
        $encodeData = $newEPayBank->getVerifyData();

        $this->assertEquals('https://gateway.newepay.online/gateway/carpay/V2.0', $encodeData['post_url']);
        $this->assertEquals($sourceData['number'], $encodeData['params']['merchId']);
        $this->assertEquals($sourceData['orderId'], $encodeData['params']['msgId']);
        $this->assertEquals('20170908103020', $encodeData['params']['reqTime']);
        $this->assertNotNull($encodeData['params']['cipherData']);
    }

    /**
     * 測試銀聯手機支付
     */
    public function testPhonePay()
    {
        $sourceData = [
            'orderId' => '201709070000000831',
            'number' => '123456',
            'paymentVendorId' => '1003',
            'amount' => '100',
            'username' => 'php1test',
            'postUrl' => 'newepay.online/',
            'notify_url' => 'http://payment/return.php',
            'orderCreateDate' => '2017-09-08 10:30:20',
            'rsa_public_key' => $this->rsaPublicKey,
        ];

        $newEPayBank = new NewEPayBank();
        $newEPayBank->setPrivateKey('1234');
        $newEPayBank->setOptions($sourceData);
        $encodeData = $newEPayBank->getVerifyData();

        $this->assertEquals('https://quick.newepay.online/quick/order/V2.0', $encodeData['post_url']);
        $this->assertEquals($sourceData['number'], $encodeData['params']['merchId']);
        $this->assertEquals($sourceData['orderId'], $encodeData['params']['msgId']);
        $this->assertEquals('20170908103020', $encodeData['params']['reqTime']);
        $this->assertNotNull($encodeData['params']['cipherData']);
        $this->assertEquals('rsa', $encodeData['params']['encryptType']);
        $this->assertEquals($sourceData['username'], $encodeData['params']['accountNo']);
    }

    /**
     * 測試解密基本參數設定沒有帶入privateKey的情況
     */
    public function testReturnWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $newEPayBank = new NewEPayBank();
        $newEPayBank->verifyOrderPayment([]);
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

        $newEPayBank = new NewEPayBank();
        $newEPayBank->setPrivateKey('1234');
        $newEPayBank->verifyOrderPayment([]);
    }

    /**
     * 測試返回時取得商家私鑰為空字串
     */
    public function testReturnGetRsaPrivateKeyEmpty()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Rsa private key is empty',
            180092
        );

        $sourceData = [
            'cipherData' => 'abcdef',
            'merchId' => '12345',
            'msgId' => '201709070000000831',
            'respCode' => '00',
            'respMsg' => '操作成功',
            'rsa_private_key' => '',
        ];

        $newEPayBank = new NewEPayBank();
        $newEPayBank->setPrivateKey('1234');
        $newEPayBank->setOptions($sourceData);
        $newEPayBank->verifyOrderPayment([]);
    }

    /**
     * 測試返回時取得商家私鑰失敗
     */
    public function testReturnGetRsaPrivateKeyError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Get rsa private key failure',
            180093
        );

        $sourceData = [
            'cipherData' => 'abcdef',
            'merchId' => '12345',
            'msgId' => '201709070000000831',
            'respCode' => '00',
            'respMsg' => '操作成功',
            'rsa_private_key' => '123456',
        ];

        $newEPayBank = new NewEPayBank();
        $newEPayBank->setPrivateKey('1234');
        $newEPayBank->setOptions($sourceData);
        $newEPayBank->verifyOrderPayment([]);
    }

    /**
     * 測試未返回sign
     */
    public function testReturnWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $encodeData = [
            'merchId' => '025000000000039',
            'orderNo' => '201709070000000831',
            'origRespCode' => '0030',
            'origRespDesc' => '支付失敗',
            'respCode' => '00',
            'respDesc' => '操作成功',
            'spbillno' => '3000320170907143922054326797',
            'transAmt' => '100',
        ];

        $cipherData = $this->getCipherData($encodeData);

        $sourceData = [
            'cipherData' => $cipherData,
            'merchId' => '12345',
            'msgId' => '201709070000000831',
            'respCode' => '00',
            'respMsg' => '操作成功',
            'rsa_private_key' => $this->rsaPrivateKey,
        ];

        $newEPayBank = new NewEPayBank();
        $newEPayBank->setPrivateKey('1234');
        $newEPayBank->setOptions($sourceData);
        $newEPayBank->verifyOrderPayment([]);
    }

    /**
     * 測試返回時簽名錯誤
     */
    public function testReturnSignatureVerificationFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Signature verification failed',
            180034
        );

        $encodeData = [
            'merchId' => '025000000000039',
            'orderNo' => '201709070000000831',
            'origRespCode' => '0030',
            'origRespDesc' => '支付失敗',
            'respCode' => '00',
            'respDesc' => '操作成功',
            'spbillno' => '3000320170907143922054326797',
            'transAmt' => '100',
            'sign' => '987456321',
        ];

        $cipherData = $this->getCipherData($encodeData);

        $sourceData = [
            'cipherData' => $cipherData,
            'merchId' => '12345',
            'msgId' => '201709070000000831',
            'respCode' => '00',
            'respMsg' => '操作成功',
            'rsa_private_key' => $this->rsaPrivateKey,
        ];

        $newEPayBank = new NewEPayBank();
        $newEPayBank->setPrivateKey('1234');
        $newEPayBank->setOptions($sourceData);
        $newEPayBank->verifyOrderPayment([]);
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

        $encodeData = [
            'merchId' => '025000000000039',
            'orderNo' => '201709070000000831',
            'origRespCode' => '0030',
            'origRespDesc' => '支付失敗',
            'respCode' => '00',
            'respDesc' => '操作成功',
            'spbillno' => '3000320170907143922054326797',
            'transAmt' => '100',
        ];

        $sign = $this->getSign($encodeData);
        $encodeData['sign'] = $sign;
        $cipherData = $this->getCipherData($encodeData);

        $sourceData = [
            'cipherData' => $cipherData,
            'merchId' => '12345',
            'msgId' => '201709070000000831',
            'respCode' => '00',
            'respMsg' => '操作成功',
            'rsa_private_key' => $this->rsaPrivateKey,
        ];

        $newEPayBank = new NewEPayBank();
        $newEPayBank->setPrivateKey('1234');
        $newEPayBank->setOptions($sourceData);
        $newEPayBank->verifyOrderPayment([]);
    }

    /**
     * 測試返回訂單號錯誤
     */
    public function testReturnOrderIdError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $encodeData = [
            'merchId' => '025000000000039',
            'orderNo' => '201709070000000831',
            'origRespCode' => '0000',
            'origRespDesc' => '支付成功',
            'respCode' => '00',
            'respDesc' => '操作成功',
            'spbillno' => '3000320170907143922054326797',
            'transAmt' => '100',
        ];

        $sign = $this->getSign($encodeData);
        $encodeData['sign'] = $sign;
        $cipherData = $this->getCipherData($encodeData);

        $sourceData = [
            'cipherData' => $cipherData,
            'merchId' => '12345',
            'msgId' => '201709070000000831',
            'respCode' => '00',
            'respMsg' => '操作成功',
            'rsa_private_key' => $this->rsaPrivateKey,
        ];

        $entry = [
            'id' => '201709070000000832',
            'amount' => '1',
        ];

        $newEPayBank = new NewEPayBank();
        $newEPayBank->setPrivateKey('1234');
        $newEPayBank->setOptions($sourceData);
        $newEPayBank->verifyOrderPayment($entry);
    }

    /**
     * 測試返回金額錯誤
     */
    public function testReturnOrderAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $encodeData = [
            'merchId' => '025000000000039',
            'orderNo' => '201709070000000831',
            'origRespCode' => '0000',
            'origRespDesc' => '支付成功',
            'respCode' => '00',
            'respDesc' => '操作成功',
            'spbillno' => '3000320170907143922054326797',
            'transAmt' => '100',
        ];

        $sign = $this->getSign($encodeData);
        $encodeData['sign'] = $sign;
        $cipherData = $this->getCipherData($encodeData);

        $sourceData = [
            'cipherData' => $cipherData,
            'merchId' => '12345',
            'msgId' => '201709070000000831',
            'respCode' => '00',
            'respMsg' => '操作成功',
            'rsa_private_key' => $this->rsaPrivateKey,
        ];

        $entry = [
            'id' => '201709070000000831',
            'amount' => '100',
        ];

        $newEPayBank = new NewEPayBank();
        $newEPayBank->setPrivateKey('1234');
        $newEPayBank->setOptions($sourceData);
        $newEPayBank->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果
     */
    public function testPaySuccess()
    {
        $encodeData = [
            'merchId' => '025000000000039',
            'orderNo' => '201709070000000831',
            'origRespCode' => '0000',
            'origRespDesc' => '支付成功',
            'respCode' => '00',
            'respDesc' => '操作成功',
            'spbillno' => '3000320170907143922054326797',
            'transAmt' => '100',
        ];

        $sign = $this->getSign($encodeData);
        $encodeData['sign'] = $sign;
        $cipherData = $this->getCipherData($encodeData);

        $sourceData = [
            'cipherData' => $cipherData,
            'merchId' => '12345',
            'msgId' => '201709070000000831',
            'respCode' => '00',
            'respMsg' => '操作成功',
            'rsa_private_key' => $this->rsaPrivateKey,
        ];

        $entry = [
            'id' => '201709070000000831',
            'amount' => '1',
        ];

        $newEPayBank = new NewEPayBank();
        $newEPayBank->setPrivateKey('1234');
        $newEPayBank->setOptions($sourceData);
        $newEPayBank->verifyOrderPayment($entry);

        $this->assertEquals('000000', $newEPayBank->getMsg());
    }

    /**
     * 組成支付平台回傳的cipherData
     *
     * @param array $encodeData
     * @return string
     */
    private function getCipherData($encodeData)
    {
        $content = trim(base64_decode($this->rsaPublicKey));

        $publicKey = openssl_pkey_get_public($content);

        ksort($encodeData);
        $json = json_encode($encodeData, JSON_UNESCAPED_UNICODE);

        $cipherData = '';
        foreach (str_split($json, 64) as $chunk) {
            $contentData = '';
            openssl_public_encrypt($chunk, $contentData, $publicKey, OPENSSL_PKCS1_PADDING);
            $cipherData .= $contentData;
        }

        return base64_encode($cipherData);
    }

    /**
     * 組成支付平台回傳的sign
     *
     * @param array $encodeData
     * @return string
     */
    private function getSign($encodeData)
    {
        // 針對$encodeData按字母做升序排列
        ksort($encodeData);

        // 依key1=value1&key2=value2&...&keyN=valueN組成字串
        $encodeStr = urldecode(http_build_query($encodeData));

        // utf-8轉gbk
        $str = iconv('UTF-8', 'gbk', $encodeStr);

        // 兩次MD5
        $sign = md5(md5($str . '&key=1234') . '&key=1234');

        return $sign;
    }
}
