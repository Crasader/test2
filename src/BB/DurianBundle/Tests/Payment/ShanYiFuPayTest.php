<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\ShanYiFuPay;
use Buzz\Message\Response;

class ShanYiFuPayTest extends DurianTestCase
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

    public function setUp()
    {
        parent::setUp();

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

        $shanYiFuPay = new ShanYiFuPay();
        $shanYiFuPay->getVerifyData();
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

        $shanYiFuPay = new ShanYiFuPay();

        $shanYiFuPay->setPrivateKey('1234');
        $shanYiFuPay->getVerifyData();
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
            'orderId' => '201804090000000831',
            'number' => 'SYF201803080000',
            'paymentVendorId' => '9999',
            'amount' => '100',
            'notify_url' => 'http://payment/return.php',
        ];

        $shanYiFuPay = new ShanYiFuPay();
        $shanYiFuPay->setPrivateKey('1234');
        $shanYiFuPay->setOptions($sourceData);
        $shanYiFuPay->getVerifyData();
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
            'orderId' => '201804090000000831',
            'number' => 'SYF201803080000',
            'paymentVendorId' => '1090',
            'amount' => '100',
            'notify_url' => 'http://payment/return.php',
            'postUrl' => 'payment.http.test',
            'orderCreateDate' => '2018-04-09 10:30:20',
            'rsa_public_key' => '',
        ];

        $shanYiFuPay = new ShanYiFuPay();
        $shanYiFuPay->setPrivateKey('1234');
        $shanYiFuPay->setOptions($sourceData);
        $shanYiFuPay->getVerifyData();
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
            'orderId' => '201804090000000831',
            'number' => 'SYF201803080000',
            'paymentVendorId' => '1090',
            'amount' => '100',
            'notify_url' => 'http://payment/return.php',
            'postUrl' => 'payment.http.test',
            'orderCreateDate' => '2018-04-09 10:30:20',
            'rsa_public_key' => 'public_key_test',
        ];

        $shanYiFuPay = new ShanYiFuPay();
        $shanYiFuPay->setPrivateKey('1234');
        $shanYiFuPay->setOptions($sourceData);
        $shanYiFuPay->getVerifyData();
    }

    /**
     * 測試支付時未返回stateCode
     */
    public function testPayNoReturnStateCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = [
            'merNo' => 'SYF201803080000',
            'msg' => '提交成功',
            'orderNum' => '201804090000000831',
            'qrcodeUrl' => 'http://api.ShanYiFuPay.com/api/eBankPay.action?pr=S5V6QpL',
            'sign' => '7C4DDF508171A03DB78C283A6BAA58A6',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json;charset=UTF-8');

        $sourceData = [
            'number' => 'SYF201803080000',
            'paymentVendorId' => '1090',
            'amount' => '1.00',
            'orderId' => '201804090000000831',
            'notify_url' => 'http://fufutest.000webhostapp.com/pay/',
            'postUrl' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'rsa_public_key' => $this->rsaPublicKey,
        ];

        $shanYiFuPay = new ShanYiFuPay();
        $shanYiFuPay->setContainer($this->container);
        $shanYiFuPay->setClient($this->client);
        $shanYiFuPay->setResponse($response);
        $shanYiFuPay->setPrivateKey('test');
        $shanYiFuPay->setOptions($sourceData);
        $shanYiFuPay->getVerifyData();
    }

    /**
     * 測試支付失敗
     */
    public function testPayFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '交易失败',
            180130
        );

        $result = [
            'stateCode' => '99',
            'msg' => '交易失败',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json;charset=UTF-8');

        $sourceData = [
            'number' => 'SYF201803080000',
            'paymentVendorId' => '1090',
            'amount' => '0.01',
            'orderId' => '201804090000000831',
            'notify_url' => 'http://fufutest.000webhostapp.com/pay/',
            'postUrl' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'rsa_public_key' => $this->rsaPublicKey,
        ];

        $shanYiFuPay = new ShanYiFuPay();
        $shanYiFuPay->setContainer($this->container);
        $shanYiFuPay->setClient($this->client);
        $shanYiFuPay->setResponse($response);
        $shanYiFuPay->setPrivateKey('test');
        $shanYiFuPay->setOptions($sourceData);
        $shanYiFuPay->getVerifyData();
    }

    /**
     * 測試支付時未返回qrcodeUrl
     */
    public function testPayNoReturnQrcodeUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = [
            'merNo' => 'SYF201803080000',
            'msg' => '提交成功',
            'orderNum' => '201804090000000831',
            'sign' => '7C4DDF508171A03DB78C283A6BAA58A6',
            'stateCode' => '00',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json;charset=UTF-8');

        $sourceData = [
            'number' => 'SYF201803080000',
            'paymentVendorId' => '1090',
            'amount' => '1.00',
            'orderId' => '201804090000000831',
            'notify_url' => 'http://fufutest.000webhostapp.com/pay/',
            'postUrl' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'rsa_public_key' => $this->rsaPublicKey,
        ];

        $shanYiFuPay = new ShanYiFuPay();
        $shanYiFuPay->setContainer($this->container);
        $shanYiFuPay->setClient($this->client);
        $shanYiFuPay->setResponse($response);
        $shanYiFuPay->setPrivateKey('test');
        $shanYiFuPay->setOptions($sourceData);
        $shanYiFuPay->getVerifyData();
    }

    /**
     * 測試二維支付
     */
    public function testScanPay()
    {
        $result = [
            'merNo' => 'SYF201803080000',
            'msg' => '提交成功',
            'orderNum' => '201804090000000831',
            'qrcodeUrl' => 'https://u.tnbpay.com/bGp4q2',
            'sign' => '08EC37F28D0AE78D1217DA752531B0B3',
            'stateCode' => '00',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json;charset=UTF-8');

        $sourceData = [
            'number' => 'SYF201803080000',
            'paymentVendorId' => '1090',
            'amount' => '1.00',
            'orderId' => '201804090000000831',
            'notify_url' => 'http://fufutest.000webhostapp.com/pay/',
            'postUrl' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'rsa_public_key' => $this->rsaPublicKey,
        ];

        $shanYiFuPay = new ShanYiFuPay();
        $shanYiFuPay->setContainer($this->container);
        $shanYiFuPay->setClient($this->client);
        $shanYiFuPay->setResponse($response);
        $shanYiFuPay->setPrivateKey('test');
        $shanYiFuPay->setOptions($sourceData);
        $shanYiFuPay->getVerifyData();
        $data = $shanYiFuPay->getVerifyData();

        $this->assertEmpty($data);
        $this->assertEquals('https://u.tnbpay.com/bGp4q2', $shanYiFuPay->getQrcode());
    }

    /**
     * 測試手機支付
     */
    public function testPhonePay()
    {
        $result = [
            'merNo' => 'SYF201803080000',
            'msg' => '提交成功',
            'orderNum' => '201804090000000831',
            'qrcodeUrl' => 'http://netway.637pay.com/api/zfb_to_wap.jsp?redirect_url=http://auth.coincard.cc',
            'sign' => '08EC37F28D0AE78D1217DA752531B0B3',
            'stateCode' => '00',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json;charset=UTF-8');

        $sourceData = [
            'number' => 'SYF201803080000',
            'paymentVendorId' => '1098',
            'amount' => '1.00',
            'orderId' => '201804090000000831',
            'notify_url' => 'http://fufutest.000webhostapp.com/pay/',
            'postUrl' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'rsa_public_key' => $this->rsaPublicKey,
        ];

        $shanYiFuPay = new ShanYiFuPay();
        $shanYiFuPay->setContainer($this->container);
        $shanYiFuPay->setClient($this->client);
        $shanYiFuPay->setResponse($response);
        $shanYiFuPay->setPrivateKey('test');
        $shanYiFuPay->setOptions($sourceData);
        $shanYiFuPay->getVerifyData();
        $data = $shanYiFuPay->getVerifyData();

        $this->assertEquals('http://netway.637pay.com/api/zfb_to_wap.jsp', $data['post_url']);
        $this->assertEquals('http://auth.coincard.cc',$data['params']['redirect_url']);
        $this->assertEquals('GET', $shanYiFuPay->getPayMethod());
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

        $shanYiFuPay = new ShanYiFuPay();
        $shanYiFuPay->verifyOrderPayment([]);
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

        $shanYiFuPay = new ShanYiFuPay();
        $shanYiFuPay->setPrivateKey('1234');
        $shanYiFuPay->verifyOrderPayment([]);
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
            'data' => 'U7IfV1i1PUMmfw8ovIJpO37bMAyOBD1hhPzGUc6YDwYS3tGOK7B',
            'merchNo' => 'SYF201803080000',
            'orderNum' => '201804090000000831',
            'rsa_private_key' => '',
        ];

        $shanYiFuPay = new ShanYiFuPay();
        $shanYiFuPay->setPrivateKey('1234');
        $shanYiFuPay->setOptions($sourceData);
        $shanYiFuPay->verifyOrderPayment([]);
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
            'data' => 'U7IfV1i1PUMmfw8ovIJpO37bMAyOBD1hhPzGUc6YDwYS3tGOK7B',
            'merchNo' => 'SYF201803080000',
            'orderNum' => '201804090000000831',
            'rsa_private_key' => 'SYF201803080000',
        ];

        $shanYiFuPay = new ShanYiFuPay();
        $shanYiFuPay->setPrivateKey('1234');
        $shanYiFuPay->setOptions($sourceData);
        $shanYiFuPay->verifyOrderPayment([]);
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

        $sourceData = [
            'data' => 'U7IfV1i1PUMmfw8ovIJpO37bMAyOBD1hhPzGUc6YDwYS3tGOK7B',
            'merchNo' => 'SYF201803080000',
            'orderNum' => '201804090000000831',
            'rsa_private_key' => $this->rsaPrivateKey,
        ];

        $shanYiFuPay = new ShanYiFuPay();
        $shanYiFuPay->setPrivateKey('1234');
        $shanYiFuPay->setOptions($sourceData);
        $shanYiFuPay->verifyOrderPayment([]);
    }

    /**
     * 測試缺少返回解密参数
     */
    public function testReturnWithoutDecodeParams()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $encodeData = [
            'amount' => '100',
            'goodsName' => 'php1test',
            'merNo' => 'SYF201803080000',
            'netway' => 'E_BANK_ICBC',
            'payResult' => '99',
            'payDate' => '2018-04-09 14:25:38',
            'sign' => '92895C0B41B7BCC9E4736323E4B2D5E5',
        ];

        $data = $this->getData($encodeData);

        $sourceData = [
            'data' => $data,
            'merchNo' => 'SYF201803080000',
            'orderNum' => '201804090000000831',
            'rsa_private_key' => $this->rsaPrivateKey,
        ];

        $shanYiFuPay = new ShanYiFuPay();
        $shanYiFuPay->setPrivateKey('1234');
        $shanYiFuPay->setOptions($sourceData);
        $shanYiFuPay->verifyOrderPayment([]);
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
            'amount' => '100',
            'goodsName' => 'php1test',
            'merNo' => 'SYF201803080000',
            'netway' => 'wx',
            'orderNum' => '201804090000000831',
            'payResult' => '00',
            'payDate' => '2018-04-09 14:25:38',
            'sign' => '987456321',
        ];

        $data = $this->getData($encodeData);

        $sourceData = [
            'data' => $data,
            'merchNo' => 'SYF201803080000',
            'orderNum' => '201804090000000831',
            'rsa_private_key' => $this->rsaPrivateKey,
        ];

        $shanYiFuPay = new ShanYiFuPay();
        $shanYiFuPay->setPrivateKey('1234');
        $shanYiFuPay->setOptions($sourceData);
        $shanYiFuPay->verifyOrderPayment([]);
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
            'amount' => '100',
            'goodsName' => 'php1test',
            'merNo' => 'SYF201803080000',
            'netway' => 'wx',
            'orderNum' => '201804090000000831',
            'payResult' => '99',
            'payDate' => '2018-04-09 14:25:38',
            'sign' => 'A14C63CC4007ECBA2024E927CBF83D28',
        ];

        $data = $this->getData($encodeData);

        $sourceData = [
            'data' => $data,
            'merchNo' => 'SYF201803080000',
            'orderNum' => '201804090000000831',
            'rsa_private_key' => $this->rsaPrivateKey,
        ];

        $shanYiFuPay = new ShanYiFuPay();
        $shanYiFuPay->setPrivateKey('1234');
        $shanYiFuPay->setOptions($sourceData);
        $shanYiFuPay->verifyOrderPayment([]);
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
            'amount' => '100',
            'goodsName' => 'php1test',
            'merNo' => 'SYF201803080000',
            'netway' => 'wx',
            'orderNum' => '201804090000000831',
            'payResult' => '00',
            'payDate' => '2018-04-09 14:25:38',
            'sign' => '2B2CACFEBF1D060AE27F1BA5EDB3A794',
        ];

        $data = $this->getData($encodeData);

        $sourceData = [
            'data' => $data,
            'merchNo' => 'SYF201803080000',
            'orderNum' => '201804090000000831',
            'rsa_private_key' => $this->rsaPrivateKey,
        ];

        $entry = [
            'id' => '201709070000000832',
            'amount' => '100',
        ];

        $shanYiFuPay = new ShanYiFuPay();
        $shanYiFuPay->setPrivateKey('1234');
        $shanYiFuPay->setOptions($sourceData);
        $shanYiFuPay->verifyOrderPayment($entry);
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
            'amount' => '100',
            'goodsName' => 'php1test',
            'merNo' => 'SYF201803080000',
            'netway' => 'E_BANK_ICBC',
            'orderNum' => '201804090000000831',
            'payResult' => '00',
            'payDate' => '2018-04-09 14:25:38',
            'sign' => '48B995B57A6AC2659F11977E0A546BDC',
        ];

        $data = $this->getData($encodeData);

        $sourceData = [
            'data' => $data,
            'merchNo' => 'SYF201803080000',
            'orderNum' => '201804090000000831',
            'rsa_private_key' => $this->rsaPrivateKey,
        ];

        $entry = [
            'id' => '201804090000000831',
            'amount' => '1000',
        ];

        $shanYiFuPay = new ShanYiFuPay();
        $shanYiFuPay->setPrivateKey('1234');
        $shanYiFuPay->setOptions($sourceData);
        $shanYiFuPay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果
     */
    public function testPaySuccess()
    {
        $encodeData = [
            'amount' => '100',
            'goodsName' => 'php1test',
            'merNo' => 'SYF201803080000',
            'netway' => 'E_BANK_ICBC',
            'orderNum' => '201804090000000831',
            'payResult' => '00',
            'payDate' => '2018-04-09 14:25:38',
            'sign' => '48B995B57A6AC2659F11977E0A546BDC',
        ];

        $data = $this->getData($encodeData);

        $sourceData = [
            'data' => $data,
            'merchNo' => 'SYF201803080000',
            'orderNum' => '201804090000000831',
            'rsa_private_key' => $this->rsaPrivateKey,
        ];

        $entry = [
            'id' => '201804090000000831',
            'amount' => '1',
        ];

        $shanYiFuPay = new ShanYiFuPay();
        $shanYiFuPay->setPrivateKey('1234');
        $shanYiFuPay->setOptions($sourceData);
        $shanYiFuPay->verifyOrderPayment($entry);

        $this->assertEquals('0', $shanYiFuPay->getMsg());
    }

    /**
     * 組成支付平台回傳的data
     *
     * @param array $encodeParams
     * @return string
     */
    private function getData($encodeParams)
    {
        $publicKey = base64_decode($this->rsaPublicKey);

        $encodeData = [];

        // 組織加密簽名，排除sign(加密簽名)
        foreach ($encodeParams as $key => $value) {
            if ($key != 'sign') {
                $encodeData[$key] = $value;
            }
        }

        ksort($encodeData);

        $encodeData['sign'] = $encodeParams['sign'];

        $encodeStr = json_encode($encodeData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $encParam = '';
        foreach (str_split($encodeStr, 117) as $str) {
            $data = '';
            openssl_public_encrypt($str, $data, $publicKey);
            $encParam .= $data;
        }

        return base64_encode($encParam);
    }
}
