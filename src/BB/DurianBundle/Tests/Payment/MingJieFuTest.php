<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\MingJieFu;
use Buzz\Message\Response;

class MingJieFuTest extends DurianTestCase
{
    /**
     * @var \Symfony\Component\DependencyInjection\ContainerInterface
     */
    private $container;

    /**
     * @var \Buzz\Client\Curl
     */
    private $client;

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

    /**
     * 支付時的參數
     *
     * @var array
     */
    private $sourceData;

    /**
     * 對外返回結果
     *
     * @var array
     */
    private $verifyResult;

    /**
     * 返回時的原始參數
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
        $this->rsaPrivateKey = base64_encode($privkey);

        // Get public key
        $pubkey = openssl_pkey_get_details($res);

        $this->rsaPublicKey = base64_encode($pubkey['key']);

        $mockLogger = $this->getMockBuilder('BB\DurianBundle\Logger\Payment')
            ->disableOriginalConstructor()
            ->setMethods(['record'])
            ->getMock();

        $this->container = $this->getMockBuilder('Symfony\Component\DependencyInjection\Container')
            ->setMethods(['get'])
            ->getMock();
        $this->container->expects($this->any())
            ->method('get')
            ->willReturn($mockLogger);

        $this->client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();

        $this->sourceData = [
            'number' => 'MJF201804240241',
            'paymentVendorId' => '1103',
            'orderId' => '201806060000012742',
            'amount' => '1',
            'notify_url' => 'http://return.php',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'postUrl' => 'mjzfpay.com',
            'rsa_public_key' => $this->rsaPublicKey,
        ];

        $qrcode = 'https://myun.tenpay.com/mqq/pay/qrcode.html?_wv=1027&_bid=2183&t=6Vb24acdcec6cbe4b40345d0e7dc4dc4';

        $this->verifyResult = [
            'merchNo' => 'MJF201804240241',
            'msg' => '提交成功',
            'orderNum' => '201806060000012742',
            'qrcodeUrl' => $qrcode,
            'sign' => '3F2EC1A89F6F34F3489E3A9463C69198',
            'stateCode' => '00',
        ];

        $this->returnResult = [
            'amount' => '100',
            'goodsName' => '201806060000012742',
            'merchNo' => 'MJF201804240241',
            'netwayCode' => 'QQ',
            'orderNum' => '201806060000012742',
            'payDate' => '20180606151050',
            'payStateCode' => '00',
        ];

        $this->returnResult['sign'] = '13FFAC741545CA215EAD1A06FC03990A';
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

        $mingJieFu = new MingJieFu();
        $mingJieFu->getVerifyData();
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

        $mingJieFu = new MingJieFu();
        $mingJieFu->setPrivateKey('test');
        $mingJieFu->getVerifyData();
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

        $this->sourceData['paymentVendorId'] = '9999';

        $mingJieFu = new MingJieFu();
        $mingJieFu->setPrivateKey('test');
        $mingJieFu->setOptions($this->sourceData);
        $mingJieFu->getVerifyData();
    }

    /**
     * 測試支付對外返回時沒有stateCode的情況
     */
    public function testPayReturnWithoutStatusCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        unset($this->verifyResult['stateCode']);

        $response = new Response();
        $response->setContent(json_encode($this->verifyResult));
        $response->addHeader('HTTP/1.1 200 OK');

        $mingJieFu = new MingJieFu();
        $mingJieFu->setContainer($this->container);
        $mingJieFu->setClient($this->client);
        $mingJieFu->setResponse($response);
        $mingJieFu->setPrivateKey('test');
        $mingJieFu->setOptions($this->sourceData);
        $mingJieFu->getVerifyData();
    }

    /**
     * 測試支付對外返回時沒有msg的情況
     */
    public function testPayReturnWithoutMsg()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        unset($this->verifyResult['msg']);

        $response = new Response();
        $response->setContent(json_encode($this->verifyResult));
        $response->addHeader('HTTP/1.1 200 OK');

        $mingJieFu = new MingJieFu();
        $mingJieFu->setContainer($this->container);
        $mingJieFu->setClient($this->client);
        $mingJieFu->setResponse($response);
        $mingJieFu->setPrivateKey('test');
        $mingJieFu->setOptions($this->sourceData);
        $mingJieFu->getVerifyData();
    }

    /**
     * 測試支付對外返回失敗
     */
    public function testPayReturnStatusNotSuccess()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '未找到通道，请检查金额与网关!',
            180130
        );

        $this->verifyResult['stateCode'] = '99';
        $this->verifyResult['msg'] = '未找到通道，请检查金额与网关!';

        $response = new Response();
        $response->setContent(json_encode($this->verifyResult));
        $response->addHeader('HTTP/1.1 200 OK');

        $mingJieFu = new MingJieFu();
        $mingJieFu->setContainer($this->container);
        $mingJieFu->setClient($this->client);
        $mingJieFu->setResponse($response);
        $mingJieFu->setPrivateKey('test');
        $mingJieFu->setOptions($this->sourceData);
        $mingJieFu->getVerifyData();
    }

    /**
     * 測試支付對外返回時沒有qrcodeUrl的情況
     */
    public function testPayReturnWithoutQrcodeUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        unset($this->verifyResult['qrcodeUrl']);

        $response = new Response();
        $response->setContent(json_encode($this->verifyResult));
        $response->addHeader('HTTP/1.1 200 OK');

        $mingJieFu = new MingJieFu();
        $mingJieFu->setContainer($this->container);
        $mingJieFu->setClient($this->client);
        $mingJieFu->setResponse($response);
        $mingJieFu->setPrivateKey('test');
        $mingJieFu->setOptions($this->sourceData);
        $mingJieFu->getVerifyData();
    }

    /**
     * 測試二維支付
     */
    public function testQrcodePay()
    {
        $response = new Response();
        $response->setContent(json_encode($this->verifyResult));
        $response->addHeader('HTTP/1.1 200 OK');

        $mingJieFu = new MingJieFu();
        $mingJieFu->setContainer($this->container);
        $mingJieFu->setClient($this->client);
        $mingJieFu->setResponse($response);
        $mingJieFu->setPrivateKey('test');
        $mingJieFu->setOptions($this->sourceData);
        $data = $mingJieFu->getVerifyData();

        $url = 'https://myun.tenpay.com/mqq/pay/qrcode.html?_wv=1027&_bid=2183&t=6Vb24acdcec6cbe4b40345d0e7dc4dc4';

        $this->assertEmpty($data);
        $this->assertEquals($url, $mingJieFu->getQrcode());
    }

    /**
     * 測試手機支付
     */
    public function testPhonePay()
    {
        $this->sourceData['paymentVendorId'] = '1104';

        $response = new Response();
        $response->setContent(json_encode($this->verifyResult));
        $response->addHeader('HTTP/1.1 200 OK');

        $mingJieFu = new MingJieFu();
        $mingJieFu->setContainer($this->container);
        $mingJieFu->setClient($this->client);
        $mingJieFu->setResponse($response);
        $mingJieFu->setPrivateKey('test');
        $mingJieFu->setOptions($this->sourceData);
        $data = $mingJieFu->getVerifyData();

        $this->assertEquals('https://myun.tenpay.com/mqq/pay/qrcode.html', $data['post_url']);
        $this->assertEquals('1027', $data['params']['_wv']);
        $this->assertEquals('2183', $data['params']['_bid']);
        $this->assertEquals('6Vb24acdcec6cbe4b40345d0e7dc4dc4', $data['params']['t']);
        $this->assertEquals('GET', $mingJieFu->getPayMethod());
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

        $mingJieFu = new MingJieFu();
        $mingJieFu->verifyOrderPayment([]);
    }

    /**
     * 測試返回時缺少data
     */
    public function testReturnWithoutData()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        unset($this->returnResult['data']);

        $mingJieFu = new MingJieFu();
        $mingJieFu->setPrivateKey('test');
        $mingJieFu->setOptions($this->returnResult);
        $mingJieFu->verifyOrderPayment([]);
    }

    /**
     * 測試返回時RSA簽名驗證錯誤
     */
    public function testReturnRsaSignatureVerificationFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Signature verification failed',
            180034
        );

        $encodeData = [
            'data' => 'failed',
            'merchNo' => 'MJF201804240241',
            'orderNum' => '201806060000012742',
            'rsa_private_key' => $this->rsaPrivateKey,
        ];

        $mingJieFu = new MingJieFu();
        $mingJieFu->setPrivateKey('test');
        $mingJieFu->setOptions($encodeData);
        $mingJieFu->verifyOrderPayment([]);
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

        $encodeData = [
            'data' => $this->rsaPublicKeyEncrypt([]),
            'merchNo' => 'MJF201804240241',
            'orderNum' => '201806060000012742',
            'rsa_private_key' => $this->rsaPrivateKey,
        ];

        $mingJieFu = new MingJieFu();
        $mingJieFu->setPrivateKey('test');
        $mingJieFu->setOptions($encodeData);
        $mingJieFu->verifyOrderPayment([]);
    }

    /**
     * 測試解密驗證支付平台缺少回傳sign(加密簽名)
     */
    public function testVerifyWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        unset($this->returnResult['sign']);

        $encodeData = [
            'data' => $this->rsaPublicKeyEncrypt($this->returnResult),
            'merchNo' => 'MJF201804240241',
            'orderNum' => '201806060000012742',
            'rsa_private_key' => $this->rsaPrivateKey,
        ];

        $mingJieFu = new MingJieFu();
        $mingJieFu->setPrivateKey('test');
        $mingJieFu->setOptions($encodeData);
        $mingJieFu->verifyOrderPayment([]);
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

        $this->returnResult['sign'] = 'error';

        $encodeData = [
            'data' => $this->rsaPublicKeyEncrypt($this->returnResult),
            'merchNo' => 'MJF201804240241',
            'orderNum' => '201806060000012742',
            'rsa_private_key' => $this->rsaPrivateKey,
        ];

        $mingJieFu = new MingJieFu();
        $mingJieFu->setPrivateKey('test');
        $mingJieFu->setOptions($encodeData);
        $mingJieFu->verifyOrderPayment([]);
    }

    /**
     * 測試返回支付不成功
     */
    public function testReturnPaymentFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $this->returnResult['payStateCode'] = '-1';
        $this->returnResult['sign'] = '253FB4599B6A5A4FA27745152F8877CB';

        $encodeData = [
            'data' => $this->rsaPublicKeyEncrypt($this->returnResult),
            'merchNo' => 'MJF201804240241',
            'orderNum' => '201806060000012742',
            'rsa_private_key' => $this->rsaPrivateKey,
        ];

        $mingJieFu = new MingJieFu();
        $mingJieFu->setPrivateKey('test');
        $mingJieFu->setOptions($encodeData);
        $mingJieFu->verifyOrderPayment([]);
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

        $encodeData = [
            'data' => $this->rsaPublicKeyEncrypt($this->returnResult),
            'merchNo' => 'MJF201804240241',
            'orderNum' => '201806060000012742',
            'rsa_private_key' => $this->rsaPrivateKey,
        ];

        $entry = ['id' => '201711080000005428'];

        $mingJieFu = new MingJieFu();
        $mingJieFu->setPrivateKey('test');
        $mingJieFu->setOptions($encodeData);
        $mingJieFu->verifyOrderPayment($entry);
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

        $encodeData = [
            'data' => $this->rsaPublicKeyEncrypt($this->returnResult),
            'merchNo' => 'MJF201804240241',
            'orderNum' => '201806060000012742',
            'rsa_private_key' => $this->rsaPrivateKey,
        ];

        $entry = [
            'id' => '201806060000012742',
            'amount' => '15.00',
        ];

        $mingJieFu = new MingJieFu();
        $mingJieFu->setPrivateKey('test');
        $mingJieFu->setOptions($encodeData);
        $mingJieFu->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果
     */
    public function testReturnOrder()
    {
        $encodeData = [
            'data' => $this->rsaPublicKeyEncrypt($this->returnResult),
            'merchNo' => 'MJF201804240241',
            'orderNum' => '201806060000012742',
            'rsa_private_key' => $this->rsaPrivateKey,
        ];

        $entry = [
            'id' => '201806060000012742',
            'amount' => '1',
        ];

        $mingJieFu = new MingJieFu();
        $mingJieFu->setPrivateKey('test');
        $mingJieFu->setOptions($encodeData);
        $mingJieFu->verifyOrderPayment($entry);

        $this->assertEquals('SUCCESS', $mingJieFu->getMsg());
    }

    /**
     * 組成支付平台回傳的RSA加密
     *
     * @param array $encodeData
     * @return string
     */
    private function rsaPublicKeyEncrypt($encodeData)
    {
        $content = trim(base64_decode($this->rsaPublicKey));
        $publicKey = openssl_pkey_get_public($content);

        // 明文需用公鑰加密，字串太長須分段
        ksort($encodeData);
        $plaintext = json_encode($encodeData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        $result = '';
        foreach (str_split($plaintext, MingJieFu::RSA_PUBLIC_ENCODE_BLOCKSIZE) as $block) {
            $encrypted = '';
            openssl_public_encrypt($block, $encrypted, $publicKey);
            $result .= $encrypted;
        }

        return base64_encode($result);
    }
}
