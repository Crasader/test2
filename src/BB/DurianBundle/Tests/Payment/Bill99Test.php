<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\Bill99;

class Bill99Test extends DurianTestCase
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

    public function setUp()
    {
        parent::setUp();

        // Create the keypair
        $res = openssl_pkey_new();

        $privkey = '';
        // Get private key
        openssl_pkey_export($res, $privkey);
        $this->privateKey = $privkey;

        // Get public key
        $pubkey = openssl_pkey_get_details($res);
        $this->publicKey = $pubkey['key'];
    }

    /**
     * 測試加密時沒有帶入merchantId的情況
     */
    public function testEncodeWithoutMerchantId()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No pay parameter specified',
            180145
        );

        $bill99 = new Bill99();

        $sourceData = ['merchantId' => ''];

        $bill99->setOptions($sourceData);
        $bill99->getVerifyData();
    }

    /**
     * 測試加密時未指定支付參數
     */
    public function testEncodeNoPayParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No pay parameter specified',
            180145
        );

        $bill99 = new Bill99();

        $sourceData = [
            'merchantId' => '47867',
            'number' => '',
            'rsa_private_key' => $this->privateKey
        ];

        $bill99->setOptions($sourceData);
        $bill99->getVerifyData();
    }

    /**
     * 測試加密時帶入錯誤的paymentVendorId
     */
    public function testEncodeWithErrorBank()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'PaymentVendor is not supported by PaymentGateway',
            180066
        );

        $bill99 = new Bill99();

        $sourceData = [
            'merchantId' => '47867',
            'number' => '20130809',
            'paymentVendorId' => '170',
            'amount' => '100',
            'orderId' => '2014072414125',
            'notify_url' => 'http://154.58.78.54/',
            'username' => 'bill99test',
            'orderCreateDate' => '2014/07/14 13:00:15',
            'rsa_private_key' => $this->privateKey,
            'domain' => '6',
        ];

        $bill99->setOptions($sourceData);
        $bill99->getVerifyData();
    }

    /**
     * 測試加密時取得商家私鑰為空字串
     */
    public function testEncodeGetMerchantKeyFileContentNull()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Rsa private key is empty',
            180092
        );

        $bill99 = new Bill99();

        $sourceData = [
            'merchantId' => '47867',
            'number' => '20130809',
            'paymentVendorId' => '17',
            'amount' => '100',
            'orderId' => '2014072414125',
            'notify_url' => 'http://154.58.78.54/',
            'username' => 'bill99test',
            'orderCreateDate' => '2014/07/14 13:00:15',
            'rsa_private_key' => '',
            'domain' => '6',
        ];

        $bill99->setOptions($sourceData);
        $bill99->getVerifyData();
    }

    /**
     * 測試加密時取得商家私鑰失敗
     */
    public function testEncodeGetMerchantKeyFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Get rsa private key failure',
            180093
        );

        $bill99 = new Bill99();

        $sourceData = [
            'merchantId' => '47867',
            'number' => '20130809',
            'paymentVendorId' => '17',
            'amount' => '100',
            'orderId' => '2014072414125',
            'notify_url' => 'http://154.58.78.54/',
            'username' => 'bill99test',
            'orderCreateDate' => '2014/07/14 13:00:15',
            'rsa_private_key' => 'acctest',
            'domain' => '6',
        ];

        $bill99->setOptions($sourceData);
        $bill99->getVerifyData();
    }

    /**
     * 測試加密時生成加密簽名錯誤
     */
    public function testEncodeGenerateSignatureFailure()
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

        $bill99 = new Bill99();

        $sourceData = [
            'merchantId' => '47867',
            'number' => '20130809',
            'paymentVendorId' => '17',
            'amount' => '100',
            'orderId' => '2014072414125',
            'notify_url' => 'http://154.58.78.54/',
            'username' => 'bill99test',
            'orderCreateDate' => '2014/07/14 13:00:15',
            'rsa_private_key' => base64_encode($privkey),
            'domain' => '6',
        ];

        $bill99->setOptions($sourceData);
        $bill99->getVerifyData();
    }

    /**
     * 測試加密
     */
    public function testEncode()
    {
        $sourceData = [
            'merchantId' => '47867',
            'number' => '20130809',
            'paymentVendorId' => '17',
            'amount' => '100',
            'orderId' => '2014072414125',
            'notify_url' => 'http://esball.org/app/member/pay_online2/pay_result.php',
            'username' => 'bill99test',
            'orderCreateDate' => '2014/07/14 13:00:15',
            'rsa_private_key' => base64_encode($this->privateKey),
            'domain' => '6',
        ];

        $bill99 = new Bill99();
        $bill99->setOptions($sourceData);
        $encodeData = $bill99->getVerifyData();

        $notifyUrl = sprintf(
            '%s?pay_system=%s&hallid=%s',
            $sourceData['notify_url'],
            $sourceData['merchantId'],
            $sourceData['domain']
        );

        $encodeStr = 'inputCharset=1&bgUrl=%s&version=v2.0&language=1&' .
            'signType=4&merchantAcctId=%s&payerName=%s&payerContactType=1&' .
            'orderId=%s&orderAmount=%s&orderTime=20140714130015&' .
            'productName=%s&payType=10&bankId=BOC&redoFlag=1';

        $encodeStr = sprintf(
            $encodeStr,
            $notifyUrl,
            $sourceData['number'],
            $sourceData['username'],
            $sourceData['orderId'],
            $sourceData['amount'] * 100,
            $sourceData['username']
        );

        $sign = '';
        openssl_sign($encodeStr, $sign, $this->privateKey);

        $stringSign = base64_encode($sign);

        $this->assertEquals($sourceData['number'], $encodeData['merchantAcctId']);
        $this->assertEquals('BOC', $encodeData['bankId']);
        $this->assertSame(round($sourceData['amount'] * 100, 0), $encodeData['orderAmount']);
        $this->assertEquals($sourceData['orderId'], $encodeData['orderId']);
        $this->assertEquals($notifyUrl, $encodeData['bgUrl']);
        $this->assertEquals($sourceData['username'], $encodeData['payerName']);
        $this->assertEquals($sourceData['username'], $encodeData['productName']);
        $this->assertEquals('20140714130015', $encodeData['orderTime']);
        $this->assertEquals($stringSign, $encodeData['signMsg']);
    }

    /**
     * 測試解密時取得商家公鑰為空字串
     */
    public function testDecodeGetMerchantKeyFileContentNull()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Rsa public key is empty',
            180095
        );

        $bill99 = new Bill99();

        $sourceData = [
           'merchantAcctId' => '20130809',
            'version' => 'v2.0',
            'language' => '1',
            'signType' => '4',
            'payType' => '10',
            'bankId' => '17',
            'orderId' => '2014072414125',
            'orderTime' => '20140714130015',
            'orderAmount' => '10000',
            'dealId' => '201407251456',
            'bankDealId' => '',
            'dealTime' => '20140715130015',
            'payAmount' => '10000',
            'fee' => '',
            'ext1' => '',
            'ext2' => '',
            'payResult' => '10',
            'errCode' => '',
            'signMsg' => 'test',
            'rsa_public_key' => ''
        ];

        $bill99->setOptions($sourceData);
        $bill99->verifyOrderPayment([]);
    }

    /**
     * 測試解密時取得商家公鑰失敗
     */
    public function testDecodeGetMerchantKeyFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Get rsa public key failure',
            180096
        );

        $bill99 = new Bill99();

        $sourceData = [
            'merchantAcctId' => '20130809',
            'version' => 'v2.0',
            'language' => '1',
            'signType' => '4',
            'payType' => '10',
            'bankId' => '17',
            'orderId' => '2014072414125',
            'orderTime' => '20140714130015',
            'orderAmount' => '10000',
            'dealId' => '201407251456',
            'bankDealId' => '',
            'dealTime' => '20140715130015',
            'payAmount' => '10000',
            'fee' => '',
            'ext1' => '',
            'ext2' => '',
            'payResult' => '10',
            'errCode' => '',
            'signMsg' => 'test',
            'rsa_public_key' => 'acctest'
        ];

        $bill99->setOptions($sourceData);
        $bill99->verifyOrderPayment([]);
    }

    /**
     * 測試解密驗證支付平台沒有回傳指定參數
     */
    public function testDecodeNoReturnParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $bill99 = new Bill99();

        $sourceData = [
            'merchantAcctId' => '20130809',
            'version' => 'v2.0',
            'language' => '1',
            'signType' => '4',
            'payType' => '10',
            'bankId' => '17',
            'orderTime' => '20140714130015',
            'orderAmount' => '10000',
            'dealId' => '201407251456',
            'bankDealId' => '',
            'dealTime' => '20140715130015',
            'payAmount' => '10000',
            'fee' => '',
            'ext1' => '',
            'ext2' => '',
            'payResult' => '10',
            'errCode' => '',
            'rsa_public_key' => $this->publicKey
        ];

        $bill99->setOptions($sourceData);
        $bill99->verifyOrderPayment([]);
    }

    /**
     * 測試解密驗證支付平台沒有回傳signMsg(簽名字符串)
     */
    public function testDecodeWithoutSignMsg()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $bill99 = new Bill99();

        $sourceData = [
            'merchantAcctId' => '20130809',
            'version' => 'v2.0',
            'language' => '1',
            'signType' => '4',
            'payType' => '10',
            'bankId' => '17',
            'orderId' => '2014072414125',
            'orderTime' => '20140714130015',
            'orderAmount' => '10000',
            'dealId' => '201407251456',
            'bankDealId' => '',
            'dealTime' => '20140715130015',
            'payAmount' => '10000',
            'fee' => '',
            'ext1' => '',
            'ext2' => '',
            'payResult' => '10',
            'errCode' => '',
            'rsa_public_key' => $this->publicKey
        ];

        $bill99->setOptions($sourceData);
        $bill99->verifyOrderPayment([]);
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

        $bill99 = new Bill99();

        $sourceData = [
            'merchantAcctId' => '20130809',
            'version' => 'v2.0',
            'language' => '1',
            'signType' => '4',
            'payType' => '10',
            'bankId' => '17',
            'orderId' => '2014072414125',
            'orderTime' => '20140714130015',
            'orderAmount' => '10000',
            'dealId' => '201407251456',
            'bankDealId' => '',
            'dealTime' => '20140715130015',
            'payAmount' => '10000',
            'fee' => '',
            'ext1' => '',
            'ext2' => '',
            'payResult' => '10',
            'errCode' => '',
            'signMsg' => 'merchantAccId=20130809',
            'rsa_public_key' => base64_encode($this->publicKey)
        ];

        $bill99->setOptions($sourceData);
        $bill99->verifyOrderPayment([]);
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

        $bill99 = new Bill99();

        $encodeStr = 'merchantAcctId=20130809&version=v2.0&language=1&signType=4&' .
            'payType=10&bankId=17&orderId=2014072414125&orderTime=20140714130015&' .
            'orderAmount=10000&dealId=201407251456&dealTime=20140715130015&' .
            'payAmount=10000&payResult=01';

        $sign = '';
        openssl_sign($encodeStr, $sign, $this->privateKey);

        $stringSign = base64_encode($sign);

        $sourceData = [
            'merchantAcctId' => '20130809',
            'version' => 'v2.0',
            'language' => '1',
            'signType' => '4',
            'payType' => '10',
            'bankId' => '17',
            'orderId' => '2014072414125',
            'orderTime' => '20140714130015',
            'orderAmount' => '10000',
            'dealId' => '201407251456',
            'bankDealId' => '',
            'dealTime' => '20140715130015',
            'payAmount' => '10000',
            'fee' => '',
            'ext1' => '',
            'ext2' => '',
            'payResult' => '01',
            'errCode' => '',
            'signMsg' => $stringSign,
            'rsa_public_key' => base64_encode($this->publicKey)
        ];

        $bill99->setOptions($sourceData);
        $bill99->verifyOrderPayment([]);
    }

    /**
     * 測試線上支付單號不正確的情況
     */
    public function testOrderIdError()
    {
        $this->setExpectedException('BB\DurianBundle\Exception\PaymentException', 'Order Id error', 180061);

        $bill99 = new Bill99();

        $encodeStr = 'merchantAcctId=20130809&version=v2.0&language=1&signType=4&' .
            'payType=10&bankId=17&orderId=2014072414125&orderTime=20140714130015&' .
            'orderAmount=10000&dealId=201407251456&dealTime=20140715130015&' .
            'payAmount=10000&payResult=10';

        $sign = '';

        openssl_sign($encodeStr, $sign, $this->privateKey);

        $stringSign = base64_encode($sign);

        $sourceData = [
            'merchantAcctId' => '20130809',
            'version' => 'v2.0',
            'language' => '1',
            'signType' => '4',
            'payType' => '10',
            'bankId' => '17',
            'orderId' => '2014072414125',
            'orderTime' => '20140714130015',
            'orderAmount' => '10000',
            'dealId' => '201407251456',
            'bankDealId' => '',
            'dealTime' => '20140715130015',
            'payAmount' => '10000',
            'fee' => '',
            'ext1' => '',
            'ext2' => '',
            'payResult' => '10',
            'errCode' => '',
            'signMsg' => $stringSign,
            'rsa_public_key' => base64_encode($this->publicKey)
        ];

        $entry = ['id' => '2014072414124'];

        $bill99->setOptions($sourceData);
        $bill99->verifyOrderPayment($entry);
    }

    /**
     * 測試線上支付金額不正確的情況
     */
    public function testOrderAmountError()
    {
        $this->setExpectedException('BB\DurianBundle\Exception\PaymentException', 'Order Amount error', 180058);

        $bill99 = new Bill99();

        $encodeStr = 'merchantAcctId=20130809&version=v2.0&language=1&signType=4&' .
            'payType=10&bankId=17&orderId=2014072414125&orderTime=20140714130015&' .
            'orderAmount=10000&dealId=201407251456&dealTime=20140715130015&' .
            'payAmount=10000&payResult=10';

        $sign = '';

        openssl_sign($encodeStr, $sign, $this->privateKey);

        $stringSign = base64_encode($sign);

        $sourceData = [
            'merchantAcctId' => '20130809',
            'version' => 'v2.0',
            'language' => '1',
            'signType' => '4',
            'payType' => '10',
            'bankId' => '17',
            'orderId' => '2014072414125',
            'orderTime' => '20140714130015',
            'orderAmount' => '10000',
            'dealId' => '201407251456',
            'bankDealId' => '',
            'dealTime' => '20140715130015',
            'payAmount' => '10000',
            'fee' => '',
            'ext1' => '',
            'ext2' => '',
            'payResult' => '10',
            'errCode' => '',
            'signMsg' => $stringSign,
            'rsa_public_key' => base64_encode($this->publicKey)
        ];

        $entry = [
            'id' => '2014072414125',
            'amount' => '9900'
        ];

        $bill99->setOptions($sourceData);
        $bill99->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testPaySuccess()
    {
        $bill99 = new Bill99();

        $encodeStr = 'merchantAcctId=20130809&version=v2.0&language=1&signType=4&' .
            'payType=10&bankId=17&orderId=2014072414125&orderTime=20140714130015&' .
            'orderAmount=10000&dealId=201407251456&dealTime=20140715130015&' .
            'payAmount=10000&payResult=10';

        $sign = '';

        openssl_sign($encodeStr, $sign, $this->privateKey);

        $stringSign = base64_encode($sign);

        $sourceData = [
            'merchantAcctId' => '20130809',
            'version' => 'v2.0',
            'language' => '1',
            'signType' => '4',
            'payType' => '10',
            'bankId' => '17',
            'orderId' => '2014072414125',
            'orderTime' => '20140714130015',
            'orderAmount' => '10000',
            'dealId' => '201407251456',
            'bankDealId' => '',
            'dealTime' => '20140715130015',
            'payAmount' => '10000',
            'fee' => '',
            'ext1' => '',
            'ext2' => '',
            'payResult' => '10',
            'errCode' => '',
            'signMsg' => $stringSign,
            'rsa_public_key' => base64_encode($this->publicKey)
        ];

        $entry = [
            'id' => '2014072414125',
            'amount' => '100'
        ];

        $bill99->setOptions($sourceData);
        $bill99->verifyOrderPayment($entry);

        $this->assertEquals('<result>1</result>', $bill99->getMsg());
    }
}
