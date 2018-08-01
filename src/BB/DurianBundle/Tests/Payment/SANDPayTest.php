<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Payment\SANDPay;
use BB\DurianBundle\Tests\Functional\WebTestCase;
use Buzz\Message\Response;

class SANDPayTest extends WebTestCase
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
     * @var \Symfony\Component\DependencyInjection\ContainerInterface
     */
    private $container;

    /**
     * @var \Buzz\Client\Curl
     */
    private $client;

    public function setUp()
    {
        parent::setUp();

        $res = openssl_pkey_new();

        $privateKey = '';
        openssl_pkey_export($res, $privateKey);
        $this->privateKey = base64_encode($privateKey);

        // 產生憑證，用於對出款回調參數加密
        $csrKey = openssl_csr_new([], $res);
        $csrSign = openssl_csr_sign($csrKey, null, $res, 365);
        openssl_x509_export($csrSign, $cert);
        $this->publicKey = $cert;

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

        $this->client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();
    }

    /**
     * 測試出款未指定出款參數
     */
    public function testWithdrawNoWithdrawParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No withdraw parameter specified',
            150180196
        );

        $sourceData = ['account' => ''];

        $sANDPay = new SANDPay();
        $sANDPay->setOptions($sourceData);
        $sANDPay->withdrawPayment();
    }

    /**
     * 測試出款時公鑰為空
     */
    public function testWithdrawGetRsaPublicKeyEmpty()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Rsa public key is empty',
            180095
        );

        $sourceData = [
            'number' => '10921654',
            'orderCreateDate' => '2018-04-17 10:40:05',
            'orderId' => '10000000000036',
            'amount' => '2',
            'account' => '6215590605000521773',
            'nameReal' => '吴坚',
            'bank_info_id' => '11',
            'shop_url' => 'http://pay.test/pay/',
            'withdraw_host' => 'payment.http.withdraw.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'rsa_public_key' => '',
            'rsa_private_key' => $this->privateKey,
        ];

        $sANDPay = new SANDPay();
        $sANDPay->setOptions($sourceData);
        $sANDPay->withdrawPayment();
    }

    /**
     * 測試支付異步返回通知驗簽時取得公鑰失敗
     */
    public function testReturnGetRsaPublicKeyFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Get rsa public key failure',
            180096
        );

        $cert = '';
        $newKey = openssl_pkey_new();
        $csrKey = openssl_csr_new([], $newKey);
        $csrSign = openssl_csr_sign($csrKey, null, $newKey, 365);
        openssl_x509_export($csrSign, $cert);

        $dropBegin = str_replace('-----BEGIN CERTIFICATE-----', '', $cert);
        $dropEnd = trim(str_replace('-----END CERTIFICATE-----', '', $dropBegin));
        $dropLine = str_replace("\n", '', $dropEnd);
        $pkcsPublic = wordwrap($dropLine, 64, "\n");

        $sourceData = [
            'number' => '10921654',
            'orderCreateDate' => '2018-04-17 10:40:05',
            'orderId' => '10000000000036',
            'amount' => '2',
            'account' => '6215590605000521773',
            'nameReal' => '吴坚',
            'bank_info_id' => '11',
            'shop_url' => 'http://pay.test/pay/',
            'withdraw_host' => 'payment.http.withdraw.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'rsa_public_key' => base64_decode($pkcsPublic),
            'rsa_private_key' => $this->privateKey,
        ];

        $sANDPay = new SANDPay();
        $sANDPay->setOptions($sourceData);
        $sANDPay->withdrawPayment();
    }

    /**
     * 測試出款加密簽名參數失敗
     */
    public function testGetWithdrawEncodeGenerateSignatureFailure()
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
        $res = openssl_pkey_new($config);

        $privateKey = '';
        openssl_pkey_export($res, $privateKey);
        $privateKey = base64_encode($privateKey);

        $cert = '';
        $newKey = openssl_pkey_new();
        $csrKey = openssl_csr_new([], $newKey);
        $csrSign = openssl_csr_sign($csrKey, null, $newKey, 365);
        openssl_x509_export($csrSign, $cert);

        $dropBegin = str_replace('-----BEGIN CERTIFICATE-----', '', $cert);
        $dropEnd = trim(str_replace('-----END CERTIFICATE-----', '', $dropBegin));
        $dropLine = str_replace("\n", '', $dropEnd);
        $pkcsPublic = wordwrap($dropLine, 64, "\n");

        $sourceData = [
            'number' => '10921654',
            'orderCreateDate' => '2018-04-17 10:40:05',
            'orderId' => '10000000000036',
            'amount' => '2',
            'account' => '6215590605000521773',
            'nameReal' => '吴坚',
            'bank_info_id' => '11',
            'shop_url' => 'http://pay.test/pay/',
            'withdraw_host' => 'payment.http.withdraw.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'rsa_public_key' => $pkcsPublic,
            'rsa_private_key' => $privateKey,
        ];

        $sANDPay = new SANDPay();
        $sANDPay->setOptions($sourceData);
        $sANDPay->withdrawPayment();
    }

    /**
     * 測試出款但返回結果缺少參數
     */
    public function testWithdrawButNoWithdrawReturnParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No withdraw return parameter specified',
            150180209
        );

        $cert = '';
        $newKey = openssl_pkey_new();
        $csrKey = openssl_csr_new([], $newKey);
        $csrSign = openssl_csr_sign($csrKey, null, $newKey, 365);
        openssl_x509_export($csrSign, $cert);

        $dropBegin = str_replace('-----BEGIN CERTIFICATE-----', '', $cert);
        $dropEnd = trim(str_replace('-----END CERTIFICATE-----', '', $dropBegin));
        $dropLine = str_replace("\n", '', $dropEnd);
        $pkcsPublic = wordwrap($dropLine, 64, "\n");

        $sourceData = [
            'number' => '10921654',
            'orderCreateDate' => '2018-04-17 10:40:05',
            'orderId' => '10000000000036',
            'amount' => '2',
            'account' => '6215590605000521773',
            'nameReal' => '吴坚',
            'bank_info_id' => '11',
            'shop_url' => 'http://pay.test/pay/',
            'withdraw_host' => 'payment.http.withdraw.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'rsa_public_key' => $pkcsPublic,
            'rsa_private_key' => $this->privateKey,
        ];

        $result = [
            'transCode' => 'RTPM',
            'accessPlatform' => '',
            'merId' => '10921654',
            'accessType' => '',
            'plId' => '',
            'encryptKey' => '123123131',
        ];

        $response = new Response();
        $response->setContent(http_build_query($result));
        $response->addHeader('HTTP/1.1 200 OK');

        $sANDPay = new SANDPay();
        $sANDPay->setContainer($this->container);
        $sANDPay->setClient($this->client);
        $sANDPay->setResponse($response);
        $sANDPay->setOptions($sourceData);
        $sANDPay->withdrawPayment();
    }

    /**
     * 測試出款返回失敗
     */
    public function testWithdrawFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '重复订单',
            180124
        );

        $cert = '';
        $newKey = openssl_pkey_new();
        $csrKey = openssl_csr_new([], $newKey);
        $csrSign = openssl_csr_sign($csrKey, null, $newKey, 365);
        openssl_x509_export($csrSign, $cert);

        $dropBegin = str_replace('-----BEGIN CERTIFICATE-----', '', $cert);
        $dropEnd = trim(str_replace('-----END CERTIFICATE-----', '', $dropBegin));
        $dropLine = str_replace("\n", '', $dropEnd);
        $pkcsPublic = wordwrap($dropLine, 64, "\n");

        $sourceData = [
            'number' => '10921654',
            'orderCreateDate' => '2018-04-17 10:40:05',
            'orderId' => '10000000000036',
            'amount' => '2',
            'account' => '6215590605000521773',
            'nameReal' => '吴坚',
            'bank_info_id' => '11',
            'shop_url' => 'http://pay.test/pay/',
            'withdraw_host' => 'payment.http.withdraw.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'rsa_public_key' => $pkcsPublic,
            'rsa_private_key' => $this->privateKey,
        ];

        // AESKey
        $aESKey = '1234567891234567';

        // AESKey加密
        $encryptKey = '';
        openssl_public_encrypt($aESKey, $encryptKey, $this->publicKey, OPENSSL_PKCS1_PADDING);

        $returnData = [
            'orderCode' => '10000000000038',
            'respCode' => '3001',
            'respDesc' => '重复订单',
            'resultFlag' => '1',
            'tranTime' => '20180417162750',
        ];
        $params = json_encode($returnData);
        $encryptData = openssl_encrypt($params, 'AES-128-ECB', $aESKey, 1);

        $result = [
            'transCode' => 'RTPM',
            'accessPlatform' => '',
            'merId' => '10921654',
            'accessType' => '',
            'plId' => '',
            'encryptKey' => base64_encode($encryptKey),
            'encryptData' => base64_encode($encryptData),
        ];

        $response = new Response();
        $response->setContent(http_build_query($result));
        $response->addHeader('HTTP/1.1 200 OK');

        $sANDPay = new SANDPay();
        $sANDPay->setContainer($this->container);
        $sANDPay->setClient($this->client);
        $sANDPay->setResponse($response);
        $sANDPay->setOptions($sourceData);
        $sANDPay->withdrawPayment();
    }

    /**
     * 測試出款返回成功
     */
    public function testWithdrawSuccess()
    {
        $cert = '';
        $newKey = openssl_pkey_new();
        $csrKey = openssl_csr_new([], $newKey);
        $csrSign = openssl_csr_sign($csrKey, null, $newKey, 365);
        openssl_x509_export($csrSign, $cert);

        $dropBegin = str_replace('-----BEGIN CERTIFICATE-----', '', $cert);
        $dropEnd = trim(str_replace('-----END CERTIFICATE-----', '', $dropBegin));
        $dropLine = str_replace("\n", '', $dropEnd);
        $pkcsPublic = wordwrap($dropLine, 64, "\n");

        $sourceData = [
            'number' => '10921654',
            'orderCreateDate' => '2018-04-17 10:40:05',
            'orderId' => '10000000000036',
            'amount' => '2',
            'account' => '6215590605000521773',
            'nameReal' => '吴坚',
            'bank_info_id' => '11',
            'shop_url' => 'http://pay.test/pay/',
            'withdraw_host' => 'payment.http.withdraw.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'rsa_public_key' => $pkcsPublic,
            'rsa_private_key' => $this->privateKey,
        ];

        // AESKey
        $aESKey = '1234567891234567';

        // AESKey加密
        $encryptKey = '';
        openssl_public_encrypt($aESKey, $encryptKey, $this->publicKey, OPENSSL_PKCS1_PADDING);

        $returnData = [
            'orderCode' => '10000000000038',
            'respCode' => '0000',
            'respDesc' => '成功',
            'resultFlag' => '0',
            'tranTime' => '20180417162750',
        ];
        $params = json_encode($returnData);
        $encryptData = openssl_encrypt($params, 'AES-128-ECB', $aESKey, 1);

        $result = [
            'transCode' => 'RTPM',
            'accessPlatform' => '',
            'merId' => '10921654',
            'accessType' => '',
            'plId' => '',
            'encryptKey' => base64_encode($encryptKey),
            'encryptData' => base64_encode($encryptData),
        ];

        $response = new Response();
        $response->setContent(http_build_query($result));
        $response->addHeader('HTTP/1.1 200 OK');

        $gaoHuiTong = new SANDPay();
        $gaoHuiTong->setContainer($this->container);
        $gaoHuiTong->setClient($this->client);
        $gaoHuiTong->setResponse($response);
        $gaoHuiTong->setOptions($sourceData);
        $gaoHuiTong->withdrawPayment();
    }
}
