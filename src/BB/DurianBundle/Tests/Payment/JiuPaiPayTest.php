<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\JiuPaiPay;

class JiuPaiPayTest extends DurianTestCase
{
    /**
     * 私鑰
     *
     * @var string
     */
    private $privateKey;

    /**
     * @var \Symfony\Component\DependencyInjection\ContainerInterface
     */
    private $container;

    public function setUp()
    {
        parent::setUp();

        $cert = '';
        $pkcsPrivate = '';
        $newKey = openssl_pkey_new();
        $privatekey = openssl_pkey_get_private($newKey);
        $csrKey = openssl_csr_new([], $newKey);
        $csrSign = openssl_csr_sign($csrKey, null, $newKey, 365);
        openssl_x509_export($csrSign, $cert);
        openssl_pkcs12_export($cert, $pkcsPrivate, $privatekey, 'test');

        $this->privateKey = base64_encode($pkcsPrivate);

        $mockLogger = $this->getMockBuilder('BB\DurianBundle\Logger\Payment')
            ->disableOriginalConstructor()
            ->setMethods(['record'])
            ->getMock();

        $mockLogger->expects($this->any())
            ->method('record')
            ->willReturn(null);

        $this->container = $this->getMockBuilder('Symfony\Component\DependencyInjection\Container')
            ->setMethods(['get'])
            ->getMock();

        $this->container->expects($this->any())
            ->method('get')
            ->willReturn($mockLogger);
    }

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

        $sourceData = [];

        $jiuPaiPay = new JiuPaiPay();
        $jiuPaiPay->setOptions($sourceData);
        $jiuPaiPay->getVerifyData();
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

        $sourceData = ['number' => ''];

        $jiuPaiPay = new JiuPaiPay();
        $jiuPaiPay->setPrivateKey('test');
        $jiuPaiPay->setOptions($sourceData);
        $jiuPaiPay->getVerifyData();
    }

    /**
     * 測試支付設定帶入不支援的銀行
     */
    public function testSetEncodeSourceUnsupportedPaymentVendor()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'PaymentVendor is not supported by PaymentGateway',
            180066
        );

        $sourceData = [
            'number' => '800000100020007',
            'notify_url' => 'http://pay.in-action.tw/return.php',
            'orderId' => '201711140000005460',
            'orderCreateDate' => '2017-08-03 12:26:41',
            'amount' => '0.01',
            'username' => 'php1test',
            'paymentVendorId' => '99999',
        ];

        $jiuPaiPay = new JiuPaiPay();
        $jiuPaiPay->setPrivateKey('test');
        $jiuPaiPay->setOptions($sourceData);
        $jiuPaiPay->getVerifyData();
    }

    /**
     * 測試支付時取得RSA憑證為空字串
     */
    public function testPayGetRsaPrivateKeyEmpty()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Rsa private key is empty',
            180092
        );

        $sourceData = [
            'number' => '800000100020007',
            'notify_url' => 'http://pay.in-action.tw/return.php',
            'orderId' => '201711140000005460',
            'orderCreateDate' => '2017-08-03 12:26:41',
            'amount' => '0.01',
            'username' => 'php1test',
            'paymentVendorId' => '1',
            'rsa_private_key' => '',
        ];

        $jiuPaiPay = new JiuPaiPay();
        $jiuPaiPay->setPrivateKey('test');
        $jiuPaiPay->setOptions($sourceData);
        $jiuPaiPay->getVerifyData();
    }

    /**
     * 測試支付時取得RSA憑證失敗
     */
    public function testPayGetRsaPrivateKeyError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Get rsa private key failure',
            180093
        );

        $sourceData = [
            'number' => '800000100020007',
            'notify_url' => 'http://pay.in-action.tw/return.php',
            'orderId' => '201711140000005460',
            'orderCreateDate' => '2017-08-03 12:26:41',
            'amount' => '0.01',
            'username' => 'php1test',
            'paymentVendorId' => '1',
            'rsa_private_key' => '123',
        ];

        $jiuPaiPay = new JiuPaiPay();
        $jiuPaiPay->setPrivateKey('test');
        $jiuPaiPay->setOptions($sourceData);
        $jiuPaiPay->getVerifyData();
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $sourceData = [
            'number' => '800000100020007',
            'notify_url' => 'http://pay.in-action.tw/return.php',
            'orderId' => '201711140000005460',
            'orderCreateDate' => '2017-08-03 12:26:41',
            'amount' => '0.01',
            'username' => 'php1test',
            'paymentVendorId' => '1',
            'rsa_private_key' => $this->privateKey,
        ];

        $jiuPaiPay = new JiuPaiPay();
        $jiuPaiPay->setPrivateKey('test');
        $jiuPaiPay->setOptions($sourceData);
        $requestData = $jiuPaiPay->getVerifyData();

        $encodeData = [];

        foreach ($requestData as $key => $value) {
            if ($key != 'merchantSign' && $key != 'merchantCert' && $value != '') {
                $encodeData[$key] = $value;
            }
        }

        ksort($encodeData);

        $encodeStr = urldecode(http_build_query($encodeData));

        $cert = $this->getCert();
        $sign = $this->getSign($encodeStr);

        $this->assertEquals('02', $requestData['charset']);
        $this->assertEquals('1.0', $requestData['version']);
        $this->assertEquals($sourceData['number'], $requestData['merchantId']);
        $this->assertEquals('20170803122641', $requestData['requestTime']);
        $this->assertEquals($sourceData['orderId'], $requestData['requestId']);
        $this->assertEquals('rpmBankPayment', $requestData['service']);
        $this->assertEquals('RSA256', $requestData['signType']);
        $this->assertEquals($cert, $requestData['merchantCert']);
        $this->assertEquals($sign, $requestData['merchantSign']);
        $this->assertEquals($sourceData['notify_url'], $requestData['pageReturnUrl']);
        $this->assertEquals($sourceData['notify_url'], $requestData['notifyUrl']);
        $this->assertEquals($sourceData['username'], $requestData['merchantName']);
        $this->assertEquals($sourceData['username'], $requestData['memberId']);
        $this->assertEquals('20170803122641', $requestData['orderTime']);
        $this->assertEquals($sourceData['orderId'], $requestData['orderId']);
        $this->assertEquals($sourceData['amount'] * 100, $requestData['totalAmount']);
        $this->assertEquals('CNY', $requestData['currency']);
        $this->assertEquals('ICBC', $requestData['bankAbbr']);
        $this->assertEquals('0', $requestData['cardType']);
        $this->assertEquals('B2C', $requestData['payType']);
        $this->assertEquals('2', $requestData['validNum']);
        $this->assertEquals('02', $requestData['validUnit']);
        $this->assertEquals('', $requestData['showUrl']);
        $this->assertEquals($sourceData['username'], $requestData['goodsName']);
        $this->assertEquals('', $requestData['goodsId']);
        $this->assertEquals('', $requestData['goodsDesc']);
        $this->assertEquals('', $requestData['subMerchantId']);
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

        $jiuPaiPay = new JiuPaiPay();
        $jiuPaiPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時未返回serverSign
     */
    public function testReturnWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $sourceData = [
            'signType' => 'RSA256',
            'charset' => '00',
            'orderTime' => '20171114091705',
            'orderSts' => 'PD',
            'payTime' => '20171114091813',
            'acDate' => '20171114',
            'bankAbbr' => 'ICBC',
            'version' => '1.0',
            'fee' => '0',
            'amount' => '10',
            'serverCert' => '308203653082024DA00302010202081BF6DEF10C56066B300D06092A864886F70D0101050500305B31',
            'payType' => 'B2C',
            'memberId' => 'php1test',
            'merchantId' => '800000100020007',
            'orderId' => '201711140000005460',
        ];

        $jiuPaiPay = new JiuPaiPay();
        $jiuPaiPay->setOptions($sourceData);
        $jiuPaiPay->verifyOrderPayment([]);
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
            'signType' => 'RSA256',
            'charset' => '00',
            'orderTime' => '20171114091705',
            'orderSts' => 'PD',
            'payTime' => '20171114091813',
            'acDate' => '20171114',
            'bankAbbr' => 'ICBC',
            'version' => '1.0',
            'fee' => '0',
            'amount' => '10',
            'serverCert' => $this->getCert(),
            'serverSign' => '308203653082024DA00302010202081BF6DEF10C56066B300D06092A864886F70D0101050500305B31',
            'payType' => 'B2C',
            'memberId' => 'php1test',
            'merchantId' => '800000100020007',
            'orderId' => '201711140000005460',
        ];

        $jiuPaiPay = new JiuPaiPay();
        $jiuPaiPay->setOptions($sourceData);
        $jiuPaiPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時訂單未支付
     */
    public function testReturnUnpaidOrder()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Unpaid order',
            180062
        );

        $encodeData = [
            'signType' => 'RSA256',
            'charset' => '00',
            'orderTime' => '20171114091705',
            'orderSts' => 'WP',
            'payTime' => '20171114091813',
            'acDate' => '20171114',
            'bankAbbr' => 'ICBC',
            'version' => '1.0',
            'fee' => '0',
            'amount' => '10',
            'payType' => 'B2C',
            'memberId' => 'php1test',
            'merchantId' => '800000100020007',
            'orderId' => '201711140000005460',
        ];

        ksort($encodeData);

        $encodeStr = urldecode(http_build_query($encodeData));

        $sourceData = [
            'signType' => 'RSA256',
            'charset' => '00',
            'orderTime' => '20171114091705',
            'orderSts' => 'WP',
            'payTime' => '20171114091813',
            'acDate' => '20171114',
            'bankAbbr' => 'ICBC',
            'version' => '1.0',
            'fee' => '0',
            'amount' => '10',
            'serverCert' => $this->getCert(),
            'serverSign' => $this->getSign($encodeStr),
            'payType' => 'B2C',
            'memberId' => 'php1test',
            'merchantId' => '800000100020007',
            'orderId' => '201711140000005460',
        ];

        $jiuPaiPay = new JiuPaiPay();
        $jiuPaiPay->setOptions($sourceData);
        $jiuPaiPay->verifyOrderPayment([]);
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
            'signType' => 'RSA256',
            'charset' => '00',
            'orderTime' => '20171114091705',
            'orderSts' => 'CZ',
            'payTime' => '20171114091813',
            'acDate' => '20171114',
            'bankAbbr' => 'ICBC',
            'version' => '1.0',
            'fee' => '0',
            'amount' => '10',
            'payType' => 'B2C',
            'memberId' => 'php1test',
            'merchantId' => '800000100020007',
            'orderId' => '201711140000005460',
        ];

        ksort($encodeData);

        $encodeStr = urldecode(http_build_query($encodeData));

        $sourceData = [
            'signType' => 'RSA256',
            'charset' => '00',
            'orderTime' => '20171114091705',
            'orderSts' => 'CZ',
            'payTime' => '20171114091813',
            'acDate' => '20171114',
            'bankAbbr' => 'ICBC',
            'version' => '1.0',
            'fee' => '0',
            'amount' => '10',
            'serverCert' => $this->getCert(),
            'serverSign' => $this->getSign($encodeStr),
            'payType' => 'B2C',
            'memberId' => 'php1test',
            'merchantId' => '800000100020007',
            'orderId' => '201711140000005460',
        ];

        $jiuPaiPay = new JiuPaiPay();
        $jiuPaiPay->setOptions($sourceData);
        $jiuPaiPay->verifyOrderPayment([]);
    }

    /**
     * 測試線上支付單號不正確的情況
     */
    public function testReturnOrderIdError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $encodeData = [
            'signType' => 'RSA256',
            'charset' => '00',
            'orderTime' => '20171114091705',
            'orderSts' => 'PD',
            'payTime' => '20171114091813',
            'acDate' => '20171114',
            'bankAbbr' => 'ICBC',
            'version' => '1.0',
            'fee' => '0',
            'amount' => '10',
            'payType' => 'B2C',
            'memberId' => 'php1test',
            'merchantId' => '800000100020007',
            'orderId' => '201711140000005460',
        ];

        ksort($encodeData);

        $encodeStr = urldecode(http_build_query($encodeData));

        $sourceData = [
            'signType' => 'RSA256',
            'charset' => '00',
            'orderTime' => '20171114091705',
            'orderSts' => 'PD',
            'payTime' => '20171114091813',
            'acDate' => '20171114',
            'bankAbbr' => 'ICBC',
            'version' => '1.0',
            'fee' => '0',
            'amount' => '10',
            'serverCert' => $this->getCert(),
            'serverSign' => $this->getSign($encodeStr),
            'payType' => 'B2C',
            'memberId' => 'php1test',
            'merchantId' => '800000100020007',
            'orderId' => '201711140000005460',
        ];

        $entry = ['id' => '2014052200123'];

        $jiuPaiPay = new JiuPaiPay();
        $jiuPaiPay->setOptions($sourceData);
        $jiuPaiPay->verifyOrderPayment($entry);
    }

    /**
     * 測試線上支付金額不正確的情況
     */
    public function testReturnOrderAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $encodeData = [
            'signType' => 'RSA256',
            'charset' => '00',
            'orderTime' => '20171114091705',
            'orderSts' => 'PD',
            'payTime' => '20171114091813',
            'acDate' => '20171114',
            'bankAbbr' => 'ICBC',
            'version' => '1.0',
            'fee' => '0',
            'amount' => '10',
            'payType' => 'B2C',
            'memberId' => 'php1test',
            'merchantId' => '800000100020007',
            'orderId' => '201711140000005460',
        ];

        ksort($encodeData);

        $encodeStr = urldecode(http_build_query($encodeData));

        $sourceData = [
            'signType' => 'RSA256',
            'charset' => '00',
            'orderTime' => '20171114091705',
            'orderSts' => 'PD',
            'payTime' => '20171114091813',
            'acDate' => '20171114',
            'bankAbbr' => 'ICBC',
            'version' => '1.0',
            'fee' => '0',
            'amount' => '10',
            'serverCert' => $this->getCert(),
            'serverSign' => $this->getSign($encodeStr),
            'payType' => 'B2C',
            'memberId' => 'php1test',
            'merchantId' => '800000100020007',
            'orderId' => '201711140000005460',
        ];

        $entry = [
            'id' => '201711140000005460',
            'amount' => '1.0000',
        ];

        $jiuPaiPay = new JiuPaiPay();
        $jiuPaiPay->setOptions($sourceData);
        $jiuPaiPay->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testPaySuccess()
    {
        $encodeData = [
            'signType' => 'RSA256',
            'charset' => '00',
            'orderTime' => '20171114091705',
            'orderSts' => 'PD',
            'payTime' => '20171114091813',
            'acDate' => '20171114',
            'bankAbbr' => 'ICBC',
            'version' => '1.0',
            'fee' => '0',
            'amount' => '10',
            'payType' => 'B2C',
            'memberId' => 'php1test',
            'merchantId' => '800000100020007',
            'orderId' => '201711140000005460',
        ];

        ksort($encodeData);

        $encodeStr = urldecode(http_build_query($encodeData));

        $sourceData = [
            'signType' => 'RSA256',
            'charset' => '00',
            'orderTime' => '20171114091705',
            'orderSts' => 'PD',
            'payTime' => '20171114091813',
            'acDate' => '20171114',
            'bankAbbr' => 'ICBC',
            'version' => '1.0',
            'fee' => '0',
            'amount' => '10',
            'serverCert' => $this->getCert(),
            'serverSign' => $this->getSign($encodeStr),
            'payType' => 'B2C',
            'memberId' => 'php1test',
            'merchantId' => '800000100020007',
            'orderId' => '201711140000005460',
        ];

        $entry = [
            'id' => '201711140000005460',
            'amount' => '0.1',
        ];

        $jiuPaiPay = new JiuPaiPay();
        $jiuPaiPay->setOptions($sourceData);
        $jiuPaiPay->verifyOrderPayment($entry);

        $this->assertEquals('result=SUCCESS', $jiuPaiPay->getMsg());
    }

    /**
     * 組成sign
     *
     * @param string $encParam
     * @return string
     */
    private function getSign($encParam)
    {
        $passphrase = 'test';

        $content = base64_decode($this->privateKey);

        $privateCert = [];
        openssl_pkcs12_read($content, $privateCert, $passphrase);

        $key = openssl_pkey_get_private($privateCert['pkey']);

        $sign = '';
        openssl_sign($encParam, $sign, $key, OPENSSL_ALGO_SHA256);

        return chunk_split(bin2hex($sign), 2, '');
    }

    /**
     * 組成cert
     *
     * @return string
     */
    private function getCert()
    {
        $passphrase = 'test';

        $content = base64_decode($this->privateKey);

        $privateCert = [];
        openssl_pkcs12_read($content, $privateCert, $passphrase);

        $dropBegin = str_replace('-----BEGIN CERTIFICATE-----', '', $privateCert['cert']);
        $pkcsPublic = trim(str_replace('-----END CERTIFICATE-----', '', $dropBegin));
        $der = base64_decode($pkcsPublic);

        return chunk_split(bin2hex($der), 2, '');
    }
}
